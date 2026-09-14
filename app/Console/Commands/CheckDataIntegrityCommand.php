<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDataIntegrityCommand extends Command
{
    protected $signature = 'app:check-integrity {--json : Devuelve el resultado en JSON}';

    protected $description = 'Ejecuta verificaciones de solo lectura sobre la integridad operativa y contable';

    public function handle(): int
    {
        $checks = [
            $this->countCheck('sale_details_without_sale', 'Detalles de venta sin venta', 'sale_details', fn () => DB::table('sale_details')->leftJoin('sales', 'sales.id', '=', 'sale_details.sale_id')->whereNull('sales.id')->count()),
            $this->countCheck('sale_details_without_product', 'Detalles de venta sin producto', 'sale_details', fn () => DB::table('sale_details')->leftJoin('products', 'products.id', '=', 'sale_details.product_id')->whereNull('products.id')->count()),
            $this->countCheck('purchase_details_without_purchase', 'Detalles de compra sin compra', 'purchase_details', fn () => DB::table('purchase_details')->leftJoin('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')->whereNull('purchases.id')->count()),
            $this->countCheck('purchase_details_without_product', 'Detalles de compra sin producto', 'purchase_details', fn () => DB::table('purchase_details')->leftJoin('products', 'products.id', '=', 'purchase_details.product_id')->whereNull('products.id')->count()),
            $this->countCheck('inventory_movements_without_product', 'Movimientos sin producto', 'inventory_movements', fn () => DB::table('inventory_movements')->leftJoin('products', 'products.id', '=', 'inventory_movements.product_id')->whereNull('products.id')->count()),
            $this->countCheck('warehouse_stock_without_product', 'Existencias de bodega sin producto', 'warehouse_stocks', fn () => DB::table('warehouse_stocks')->leftJoin('products', 'products.id', '=', 'warehouse_stocks.product_id')->whereNull('products.id')->count()),
            $this->countCheck('warehouse_stock_without_warehouse', 'Existencias asociadas a bodega inexistente', 'warehouse_stocks', fn () => DB::table('warehouse_stocks')->leftJoin('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')->whereNull('warehouses.id')->count()),
            $this->countCheck('negative_warehouse_stock', 'Existencias negativas por bodega', 'warehouse_stocks', fn () => DB::table('warehouse_stocks')->where('quantity', '<', -0.0001)->count()),
            $this->countCheck('product_stock_mismatch', 'Stock global diferente a suma de bodegas', 'warehouse_stocks', fn () => DB::table('products')
                ->leftJoin('warehouse_stocks', 'warehouse_stocks.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.stock')
                ->havingRaw('ABS(products.stock - COALESCE(SUM(warehouse_stocks.quantity), 0)) > 0.0001')
                ->get(['products.id'])->count()),
            $this->countCheck('unbalanced_journal_headers', 'Asientos con totales descuadrados', 'journal_entries', fn () => DB::table('journal_entries')->where('status', 'posted')->whereRaw('ABS(total_debit - total_credit) > 0.01')->count()),
            $this->countCheck('unbalanced_journal_lines', 'Asientos cuyas líneas no cuadran', 'journal_entries', fn () => DB::table('journal_entries')
                ->leftJoin('journal_entry_lines', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', 'posted')
                ->groupBy('journal_entries.id')
                ->havingRaw('ABS(COALESCE(SUM(journal_entry_lines.debit), 0) - COALESCE(SUM(journal_entry_lines.credit), 0)) > 0.01')
                ->get(['journal_entries.id'])->count()),
            $this->countCheck('journal_header_line_mismatch', 'Totales de asiento diferentes a sus líneas', 'journal_entries', fn () => DB::table('journal_entries')
                ->leftJoin('journal_entry_lines', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', 'posted')
                ->groupBy('journal_entries.id', 'journal_entries.total_debit', 'journal_entries.total_credit')
                ->havingRaw('ABS(journal_entries.total_debit - COALESCE(SUM(journal_entry_lines.debit), 0)) > 0.01 OR ABS(journal_entries.total_credit - COALESCE(SUM(journal_entry_lines.credit), 0)) > 0.01')
                ->get(['journal_entries.id'])->count()),
            $this->countCheck('duplicate_open_cash_sessions', 'Usuarios con más de una caja abierta', 'caja_sessions', fn () => DB::table('caja_sessions')
                ->where('status', 'open')
                ->whereNotNull('opened_by')
                ->groupBy('opened_by')
                ->havingRaw('COUNT(*) > 1')
                ->get(['opened_by'])->count()),
            $this->countCheck('purchases_without_document_number', 'Compras sin número documental', 'purchases', fn () => Schema::hasColumn('purchases', 'document_number')
                ? DB::table('purchases')->whereNull('document_number')->orWhere('document_number', '')->count()
                : 1),
            $this->countCheck('duplicate_purchase_numbers', 'Números de compra duplicados', 'purchases', fn () => Schema::hasColumn('purchases', 'document_number')
                ? DB::table('purchases')->whereNotNull('document_number')->groupBy('document_number')->havingRaw('COUNT(*) > 1')->get(['document_number'])->count()
                : 1),
        ];

        $failures = collect($checks)->where('status', 'ERROR')->count();
        $result = [
            'status' => $failures === 0 ? 'OK' : 'ERROR',
            'failures' => $failures,
            'checks' => $checks,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['Control', 'Estado', 'Incidencias'],
                collect($checks)->map(fn (array $check) => [$check['label'], $check['status'], $check['count']])->all(),
            );
            $failures === 0
                ? $this->info('[OK] No se detectaron inconsistencias de datos.')
                : $this->error("[ERROR] {$failures} controles detectaron inconsistencias.");
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{key: string, label: string, status: string, count: int}
     */
    private function countCheck(string $key, string $label, string $requiredTable, callable $query): array
    {
        $count = Schema::hasTable($requiredTable) ? (int) $query() : 1;

        return [
            'key' => $key,
            'label' => $label,
            'status' => $count === 0 ? 'OK' : 'ERROR',
            'count' => $count,
        ];
    }
}
