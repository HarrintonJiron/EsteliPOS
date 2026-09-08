<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CreditPayment;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntryLine;
use App\Models\OperationalExpense;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private LedgerService $ledger)
    {
    }

    /**
     * Estado de Resultados (P&L) para un rango de fechas.
     */
    public function incomeStatement(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $totals = JournalEntryLine::selectRaw('account_id, SUM(debit) as debit, SUM(credit) as credit')
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'posted');
                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $build = function (string $mainGroup) use ($totals): Collection {
            return Account::postable()->ofMainGroup($mainGroup)->orderBy('code')->get()
                ->map(function (Account $account) use ($totals) {
                    $row = $totals->get($account->id);
                    $debit = (float) ($row->debit ?? 0);
                    $credit = (float) ($row->credit ?? 0);
                    $account->amount = round($account->nature === 'credit' ? $credit - $debit : $debit - $credit, 2);

                    return $account;
                })
                ->filter(fn (Account $account) => $account->amount != 0)
                ->values();
        };

        $ingresos = $build('ingresos');
        $costos = $build('costos');
        $gastos = $build('gastos');
        $otrosIngresos = $build('otros_ingresos');
        $otrosGastos = $build('otros_gastos');

        $totalIngresos = round((float) $ingresos->sum('amount'), 2);
        $totalCostos = round((float) $costos->sum('amount'), 2);
        $utilidadBruta = round($totalIngresos - $totalCostos, 2);
        $totalGastos = round((float) $gastos->sum('amount'), 2);
        $utilidadOperativa = round($utilidadBruta - $totalGastos, 2);
        $totalOtrosIngresos = round((float) $otrosIngresos->sum('amount'), 2);
        $totalOtrosGastos = round((float) $otrosGastos->sum('amount'), 2);
        $utilidadNeta = round($utilidadOperativa + $totalOtrosIngresos - $totalOtrosGastos, 2);

        return compact(
            'ingresos', 'costos', 'gastos', 'otrosIngresos', 'otrosGastos',
            'totalIngresos', 'totalCostos', 'utilidadBruta', 'totalGastos', 'utilidadOperativa',
            'totalOtrosIngresos', 'totalOtrosGastos', 'utilidadNeta'
        );
    }

    /**
     * Balance General (balance sheet) a una fecha de corte dada.
     */
    public function balanceSheet(string $asOfDate): array
    {
        $beforeDate = Carbon::parse($asOfDate)->addDay()->toDateString();

        $build = function (string $mainGroup) use ($beforeDate): Collection {
            return Account::postable()->ofMainGroup($mainGroup)->orderBy('code')->get()
                ->map(function (Account $account) use ($beforeDate) {
                    $account->amount = round($this->ledger->openingBalance($account, $beforeDate), 2);

                    return $account;
                })
                ->filter(fn (Account $account) => $account->amount != 0)
                ->values();
        };

        $activo = $build('activo');
        $pasivo = $build('pasivo');
        $capital = $build('capital');

        // Utilidad del ejercicio: resultado neto acumulado desde el inicio del año fiscal hasta la fecha de corte.
        $yearStart = Carbon::parse($asOfDate)->startOfYear()->toDateString();
        $utilidadEjercicio = $this->incomeStatement($yearStart, $asOfDate)['utilidadNeta'];

        $totalActivo = round((float) $activo->sum('amount'), 2);
        $totalPasivo = round((float) $pasivo->sum('amount'), 2);
        $totalCapitalCuentas = round((float) $capital->sum('amount'), 2);
        $totalCapital = round($totalCapitalCuentas + $utilidadEjercicio, 2);
        $diferencia = round($totalActivo - ($totalPasivo + $totalCapital), 2);

        return compact(
            'activo', 'pasivo', 'capital', 'utilidadEjercicio',
            'totalActivo', 'totalPasivo', 'totalCapitalCuentas', 'totalCapital', 'diferencia', 'asOfDate'
        );
    }

    /**
     * Flujo de Caja (método directo) para las cuentas de Caja y Banco dentro de un rango de fechas.
     */
    public function cashFlow(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cashAccounts = Account::postable()->whereIn('code', ['1.1.01', '1.1.02'])->get();
        $cashAccountIds = $cashAccounts->pluck('id');

        $openingBalance = 0.0;
        if ($dateFrom) {
            foreach ($cashAccounts as $account) {
                $openingBalance += $this->ledger->openingBalance($account, $dateFrom);
            }
        }

        $lines = JournalEntryLine::with('journalEntry')
            ->whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'posted');
                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
            })
            ->get()
            ->sortBy(fn ($line) => $line->journalEntry->date->format('Y-m-d') . '-' . str_pad((string) $line->journalEntry->id, 10, '0', STR_PAD_LEFT))
            ->values();

        $movements = $lines->map(function (JournalEntryLine $line) {
            $entry = $line->journalEntry;
            $amount = round((float) $line->debit - (float) $line->credit, 2);
            $category = match ($entry->source_type) {
                Sale::class, CreditPayment::class => 'Operación · Cobros',
                Purchase::class => 'Operación · Pagos a proveedores',
                InventoryAdjustment::class => 'Operación · Ajustes de inventario',
                OperationalExpense::class => 'Operación · Gastos operativos',
                default => 'Operación · Otros movimientos',
            };

            return [
                'date' => $entry->date->format('Y-m-d'),
                'concept' => $entry->concept,
                'reference' => $entry->reference,
                'category' => $category,
                'amount' => $amount,
            ];
        });

        $byCategory = $movements->groupBy('category')->map(fn ($group) => round((float) $group->sum('amount'), 2));
        $netMovement = round((float) $movements->sum('amount'), 2);
        $closingBalance = round($openingBalance + $netMovement, 2);

        return [
            'openingBalance' => round($openingBalance, 2),
            'movements' => $movements,
            'byCategory' => $byCategory,
            'netMovement' => $netMovement,
            'closingBalance' => $closingBalance,
        ];
    }
}
