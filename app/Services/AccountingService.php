<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CreditPayment;
use App\Models\FiscalPeriod;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntry;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    // Códigos del catálogo de cuentas (ChartOfAccountsSeeder) usados por la integración automática.
    private const ACC_CAJA = '1.1.01';
    private const ACC_BANCO = '1.1.02';
    private const ACC_CLIENTES = '1.1.04';
    private const ACC_INVENTARIO = '1.1.05';
    private const ACC_PROVEEDORES = '2.1.01';
    private const ACC_IVA_POR_PAGAR = '2.1.02';
    private const ACC_IVA_CREDITO_FISCAL = '1.1.06';
    private const ACC_VENTAS = '4.1';
    private const ACC_AJUSTE_FALTANTE = '5.2';
    private const ACC_OTROS_INGRESOS = '7.2';

    /**
     * Create a balanced journal entry with its lines inside a single transaction.
     *
     * @param  array{date:string,concept:string,reference?:string,notes?:string,user_id?:int,source_type?:string,source_id?:int,lines:array<int,array{account_id:int,detail?:string,debit?:float,credit?:float}>}  $data
     */
    public function createEntry(array $data, bool $post = false): JournalEntry
    {
        $lines = $data['lines'] ?? [];
        $this->assertBalanced($lines);
        $this->assertPeriodOpen($data['date']);

        return DB::transaction(function () use ($data, $lines, $post) {
            $entry = JournalEntry::create([
                'number' => NumberSequence::getNext('asiento'),
                'date' => $data['date'],
                'concept' => $data['concept'],
                'reference' => $data['reference'] ?? null,
                'status' => JournalEntry::STATUS_DRAFT,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
                'total_debit' => 0,
                'total_credit' => 0,
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);

                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'detail' => $line['detail'] ?? null,
                    'debit' => $debit,
                    'credit' => $credit,
                ]);

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            $entry->update([
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
            ]);

            if ($post) {
                $this->post($entry);
            }

            return $entry->fresh('lines.account');
        });
    }

    public function post(JournalEntry $entry): JournalEntry
    {
        if ($entry->status !== JournalEntry::STATUS_DRAFT) {
            throw new \RuntimeException('Solo se pueden contabilizar asientos en borrador.');
        }

        if (! $entry->isBalanced()) {
            throw new \RuntimeException('El asiento no está balanceado (Debe ≠ Haber).');
        }

        $this->assertPeriodOpen($entry->date->toDateString());

        $entry->update([
            'status' => JournalEntry::STATUS_POSTED,
            'posted_at' => now(),
        ]);

        return $entry;
    }

    public function void(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        if ($entry->status !== JournalEntry::STATUS_POSTED) {
            throw new \RuntimeException('Solo se pueden anular asientos contabilizados.');
        }

        $this->assertPeriodOpen($entry->date->toDateString());

        $entry->update([
            'status' => JournalEntry::STATUS_VOIDED,
            'voided_at' => now(),
            'notes' => trim(($entry->notes ? $entry->notes . ' | ' : '') . 'Anulado: ' . ($reason ?? 'sin motivo especificado')),
        ]);

        return $entry;
    }

    /**
     * Voids the posted journal entry linked to a given source model, if one exists (no-op otherwise).
     */
    public function voidForSource(string $sourceType, int $sourceId, ?string $reason = null): void
    {
        $entry = JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($entry) {
            $this->void($entry, $reason);
        }
    }

    /**
     * Asiento automático de una venta: Debe Caja/Banco/Clientes según forma de pago, Haber Ventas + IVA por Pagar.
     */
    public function recordSale(Sale $sale): ?JournalEntry
    {
        if ($sale->status === 'canceled') {
            return null;
        }

        $debitAccount = match ($sale->payment_type) {
            'credit' => $this->account(self::ACC_CLIENTES),
            'transfer' => $this->account(self::ACC_BANCO),
            default => $this->account(self::ACC_CAJA),
        };

        $reference = $sale->invoice_number ?? ('#' . $sale->id);

        $lines = [
            ['account_id' => $debitAccount->id, 'detail' => "Venta {$reference}", 'debit' => $sale->total, 'credit' => 0],
            ['account_id' => $this->account(self::ACC_VENTAS)->id, 'detail' => "Venta {$reference}", 'debit' => 0, 'credit' => $sale->subtotal],
        ];

        if ((float) $sale->tax_total > 0) {
            $lines[] = ['account_id' => $this->account(self::ACC_IVA_POR_PAGAR)->id, 'detail' => "IVA venta {$reference}", 'debit' => 0, 'credit' => $sale->tax_total];
        }

        return $this->createEntry([
            'date' => $sale->date instanceof \Carbon\CarbonInterface ? $sale->date->toDateString() : $sale->date,
            'concept' => "Venta {$reference}",
            'reference' => $reference,
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'user_id' => $sale->user_id,
            'lines' => $lines,
        ], post: true);
    }

    /**
     * Asiento automático de una compra: Debe Inventario, Haber Proveedores.
     */
    public function recordPurchase(Purchase $purchase): ?JournalEntry
    {
        if ($purchase->status === 'canceled' || (float) $purchase->total <= 0) {
            return null;
        }

        $reference = 'COMPRA-' . $purchase->id;
        $subtotal = (float) $purchase->subtotal;
        $taxTotal = (float) $purchase->tax_total;

        // Compras antiguas sin desglose de impuesto: todo el total va a Inventario.
        if ($subtotal <= 0 && $taxTotal <= 0) {
            $subtotal = (float) $purchase->total;
        }

        $lines = [
            ['account_id' => $this->account(self::ACC_INVENTARIO)->id, 'detail' => $reference, 'debit' => $subtotal, 'credit' => 0],
        ];

        if ($taxTotal > 0) {
            $lines[] = ['account_id' => $this->account(self::ACC_IVA_CREDITO_FISCAL)->id, 'detail' => "IVA compra {$reference}", 'debit' => $taxTotal, 'credit' => 0];
        }

        $lines[] = ['account_id' => $this->account(self::ACC_PROVEEDORES)->id, 'detail' => $reference, 'debit' => 0, 'credit' => $purchase->total];

        return $this->createEntry([
            'date' => $purchase->date instanceof \Carbon\CarbonInterface ? $purchase->date->toDateString() : $purchase->date,
            'concept' => "Compra a proveedor #{$purchase->supplier_id}",
            'reference' => $reference,
            'source_type' => Purchase::class,
            'source_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'lines' => $lines,
        ], post: true);
    }

    /**
     * Asiento automático de un abono de cliente: Debe Caja/Banco, Haber Clientes.
     */
    public function recordCreditPayment(CreditPayment $payment): ?JournalEntry
    {
        $debitAccount = $payment->payment_type === 'cash'
            ? $this->account(self::ACC_CAJA)
            : $this->account(self::ACC_BANCO);

        $lines = [
            ['account_id' => $debitAccount->id, 'detail' => 'Abono de crédito', 'debit' => $payment->amount, 'credit' => 0],
            ['account_id' => $this->account(self::ACC_CLIENTES)->id, 'detail' => 'Abono de crédito', 'debit' => 0, 'credit' => $payment->amount],
        ];

        return $this->createEntry([
            'date' => $payment->payment_date instanceof \Carbon\CarbonInterface ? $payment->payment_date->toDateString() : now()->toDateString(),
            'concept' => 'Abono a crédito de cliente',
            'reference' => $payment->reference_number,
            'source_type' => CreditPayment::class,
            'source_id' => $payment->id,
            'user_id' => $payment->user_id,
            'lines' => $lines,
        ], post: true);
    }

    /**
     * Asiento automático de un ajuste de inventario, valorizado al costo de compra del producto.
     */
    public function recordInventoryAdjustment(InventoryAdjustment $adjustment): ?JournalEntry
    {
        $product = $adjustment->product ?? Product::find($adjustment->product_id);
        $unitCost = (float) ($product?->purchase_price ?? 0);
        $amount = round(abs($adjustment->quantity) * $unitCost, 2);

        if ($amount <= 0) {
            return null;
        }

        if ($adjustment->quantity < 0) {
            // Faltante: sale del inventario y se reconoce como costo.
            $lines = [
                ['account_id' => $this->account(self::ACC_AJUSTE_FALTANTE)->id, 'detail' => $adjustment->reason, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->account(self::ACC_INVENTARIO)->id, 'detail' => $adjustment->reason, 'debit' => 0, 'credit' => $amount],
            ];
        } else {
            // Sobrante: entra al inventario y se reconoce como otro ingreso.
            $lines = [
                ['account_id' => $this->account(self::ACC_INVENTARIO)->id, 'detail' => $adjustment->reason, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->account(self::ACC_OTROS_INGRESOS)->id, 'detail' => $adjustment->reason, 'debit' => 0, 'credit' => $amount],
            ];
        }

        return $this->createEntry([
            'date' => now()->toDateString(),
            'concept' => 'Ajuste de inventario: ' . $adjustment->reason,
            'reference' => $adjustment->reference,
            'source_type' => InventoryAdjustment::class,
            'source_id' => $adjustment->id,
            'user_id' => $adjustment->user_id,
            'lines' => $lines,
        ], post: true);
    }

    private function account(string $code): Account
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            throw new \RuntimeException("Cuenta contable '{$code}' no existe. Ejecute el seeder del catálogo de cuentas (ChartOfAccountsSeeder).");
        }

        return $account;
    }

    private function assertPeriodOpen(string $date): void
    {
        if (FiscalPeriod::isDateClosed($date)) {
            throw new \RuntimeException("No se puede crear, contabilizar o anular movimientos en un período contable cerrado ({$date}).");
        }
    }

    /**
     * @param  array<int,array{account_id:int,debit?:float,credit?:float}>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        if (count($lines) < 2) {
            throw new \InvalidArgumentException('Un asiento debe tener al menos dos líneas.');
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit > 0 && $credit > 0) {
                throw new \InvalidArgumentException('Una línea no puede tener Debe y Haber al mismo tiempo.');
            }

            if ($debit <= 0 && $credit <= 0) {
                throw new \InvalidArgumentException('Cada línea debe tener un monto en Debe o en Haber.');
            }

            $account = Account::find($line['account_id']);
            if (! $account || ! $account->is_postable) {
                throw new \InvalidArgumentException('Solo se puede contabilizar en cuentas de detalle (postables).');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \InvalidArgumentException('El asiento está desbalanceado: Debe (' . number_format($totalDebit, 2) . ') ≠ Haber (' . number_format($totalCredit, 2) . ').');
        }
    }
}
