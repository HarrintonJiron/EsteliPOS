<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function stockIn(
        Product $product,
        float $quantity,
        string $reference,
        string $note,
        ?int $userId = null,
        ?int $warehouseId = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $reference, $note, $userId, $warehouseId) {
            if ($quantity <= 0) {
                throw new \InvalidArgumentException('La cantidad de inventario debe ser mayor que cero.');
            }

            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $this->incrementWarehouseStock($product, $warehouse, $quantity);
            $product->refresh();

            return InventoryMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'quantity' => $quantity,
                'stock_after' => $product->stock,
                'reference' => $reference,
                'note' => $note,
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]);
        });
    }

    public function stockOut(
        Product $product,
        float $quantity,
        string $reference,
        string $note,
        ?int $userId = null,
        bool $allowNegative = false,
        ?int $warehouseId = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $reference, $note, $userId, $allowNegative, $warehouseId) {
            if ($quantity <= 0) {
                throw new \InvalidArgumentException('La cantidad de inventario debe ser mayor que cero.');
            }

            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $this->ensureWarehouseStockInitialized($product, $warehouse);

            $available = $this->warehouseQuantity($product, $warehouse);

            if (! $allowNegative && $available < $quantity) {
                throw new \RuntimeException(
                    "Stock insuficiente en {$warehouse->name} para «{$product->name}». Disponible: {$available}"
                );
            }

            $this->decrementWarehouseStock($product, $warehouse, $quantity);
            $product->refresh();

            return InventoryMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'out',
                'quantity' => $quantity,
                'stock_after' => $product->stock,
                'reference' => $reference,
                'note' => $note,
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]);
        });
    }

    public function calculatedStock(Product $product): float
    {
        $in = (float) $product->inventoryMovements()->where('type', 'in')->sum('quantity');
        $out = (float) $product->inventoryMovements()->where('type', 'out')->sum('quantity');

        return round($in - $out, 4);
    }

    /**
     * @return array{fixed: int, discrepancies: list<array{product: Product, recorded: float, calculated: float}>}
     */
    public function reconcileAll(bool $fix = false): array
    {
        $discrepancies = [];
        $fixed = 0;

        Product::query()->with('inventoryMovements')->chunkById(100, function ($products) use (&$discrepancies, &$fixed, $fix) {
            foreach ($products as $product) {
                $calculated = $this->calculatedStock($product);

                if (abs((float) $product->stock - $calculated) > 0.0001) {
                    $discrepancies[] = [
                        'product' => $product,
                        'recorded' => (float) $product->stock,
                        'calculated' => $calculated,
                    ];

                    if ($fix) {
                        $product->update(['stock' => $calculated]);
                        $fixed++;
                    }
                }
            }
        });

        return ['fixed' => $fixed, 'discrepancies' => $discrepancies];
    }

    public function salesStatsSubquery(int $days = 30): Builder
    {
        return DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.date', '>=', now()->subDays($days))
            ->groupBy('sale_details.product_id')
            ->selectRaw('sale_details.product_id')
            ->selectRaw('COALESCE(SUM(sale_details.quantity), 0) as sold_qty')
            ->selectRaw('COALESCE(SUM(sale_details.subtotal), 0) as sold_revenue')
            ->selectRaw('COUNT(DISTINCT sale_details.sale_id) as sale_count');
    }

    public function movementStats(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'entries' => (float) InventoryMovement::where('type', 'in')->where('created_at', '>=', $since)->sum('quantity'),
            'exits' => (float) InventoryMovement::where('type', 'out')->where('created_at', '>=', $since)->sum('quantity'),
            'entry_count' => InventoryMovement::where('type', 'in')->where('created_at', '>=', $since)->count(),
            'exit_count' => InventoryMovement::where('type', 'out')->where('created_at', '>=', $since)->count(),
        ];
    }

    /**
     * @return array{labels: list<string>, entries: list<float>, exits: list<float>}
     */
    public function movementTrend(int $days = 30): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $entries = InventoryMovement::query()
            ->where('type', 'in')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(quantity) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $exits = InventoryMovement::query()
            ->where('type', 'out')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(quantity) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $entrySeries = [];
        $exitSeries = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $entrySeries[] = (float) ($entries[$key] ?? 0);
            $exitSeries[] = (float) ($exits[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'entries' => $entrySeries,
            'exits' => $exitSeries,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    public function stockHealthBreakdown(): array
    {
        $active = Product::query()->where('status', 'active');

        $out = (clone $active)->where('stock', '<=', 0)->count();
        $low = (clone $active)
            ->where('stock', '>', 0)
            ->whereRaw('stock <= COALESCE(low_stock_threshold, 10)')
            ->count();
        $expiring = (clone $active)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->count();
        $healthy = (clone $active)
            ->where('stock', '>', 0)
            ->whereRaw('stock > COALESCE(low_stock_threshold, 10)')
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>', now()->addDays(30));
            })
            ->count();

        return [
            'labels' => ['Saludable', 'Bajo stock', 'Sin stock', 'Por vencer'],
            'values' => [$healthy, $low, $out, $expiring],
            'colors' => ['#10b981', '#f59e0b', '#ef4444', '#f97316'],
        ];
    }

    /**
     * @return array<int, array{warehouse: Warehouse, quantity: float, value: float, products: int}>
     */
    public function warehouseSummary(): array
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->withSum('stocks as total_quantity', 'quantity')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(function (Warehouse $warehouse) {
                $value = (float) DB::table('warehouse_stocks')
                    ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
                    ->where('warehouse_stocks.warehouse_id', $warehouse->id)
                    ->selectRaw('COALESCE(SUM(warehouse_stocks.quantity * products.purchase_price), 0) as total')
                    ->value('total');

                $products = (int) DB::table('warehouse_stocks')
                    ->where('warehouse_id', $warehouse->id)
                    ->where('quantity', '>', 0)
                    ->count();

                return [
                    'warehouse' => $warehouse,
                    'quantity' => (float) ($warehouse->total_quantity ?? 0),
                    'value' => $value,
                    'products' => $products,
                ];
            })
            ->all();
    }

    public function nextProductCode(string $prefix = 'PROD'): string
    {
        $max = 0;
        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/';

        Product::query()
            ->where('code', 'like', $prefix.'-%')
            ->pluck('code')
            ->each(function (string $code) use ($pattern, &$max) {
                if (preg_match($pattern, $code, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            });

        return $prefix.'-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function syncProductTotalStock(Product $product): void
    {
        $total = (float) WarehouseStock::query()
            ->where('product_id', $product->id)
            ->sum('quantity');

        $product->update(['stock' => $total]);
    }

    /**
     * Transfiere existencias entre bodegas.
     */
    public function transfer(
        Product $product,
        float $quantity,
        int $fromWarehouseId,
        int $toWarehouseId,
        string $note = '',
        ?int $userId = null,
    ): void {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException('La bodega de origen y destino deben ser distintas.');
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad a transferir debe ser mayor que cero.');
        }

        DB::transaction(function () use ($product, $quantity, $fromWarehouseId, $toWarehouseId, $note, $userId) {
            $from = $this->resolveWarehouse($fromWarehouseId);
            $to = $this->resolveWarehouse($toWarehouseId);
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

            $available = $this->warehouseQuantity($locked, $from);
            if ($available < $quantity) {
                throw new \RuntimeException(
                    "Stock insuficiente en {$from->name} para «{$locked->name}». Disponible: {$available}"
                );
            }

            $this->decrementWarehouseStock($locked, $from, $quantity);
            $this->incrementWarehouseStock($locked->fresh(), $to, $quantity);
            $locked->refresh();

            $ref = 'transfer:'.$from->id.'-'.$to->id.':'.now()->timestamp;

            InventoryMovement::create([
                'product_id' => $locked->id,
                'warehouse_id' => $from->id,
                'type' => 'out',
                'quantity' => $quantity,
                'stock_after' => $locked->stock,
                'reference' => $ref,
                'note' => trim('Salida por transferencia a '.$to->name.($note ? ' · '.$note : '')),
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]);

            InventoryMovement::create([
                'product_id' => $locked->id,
                'warehouse_id' => $to->id,
                'type' => 'in',
                'quantity' => $quantity,
                'stock_after' => $locked->stock,
                'reference' => $ref,
                'note' => trim('Entrada por transferencia desde '.$from->name.($note ? ' · '.$note : '')),
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]);
        });
    }

    private function resolveWarehouse(?int $warehouseId): Warehouse
    {
        if ($warehouseId) {
            $warehouse = Warehouse::query()->where('is_active', true)->find($warehouseId);
            if ($warehouse) {
                return $warehouse;
            }
        }

        return Warehouse::default() ?? Warehouse::query()->create([
            'code' => 'BOD-01',
            'name' => 'Bodega Principal',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function warehouseQuantity(Product $product, Warehouse $warehouse): float
    {
        return (float) (WarehouseStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity') ?? 0);
    }

    private function ensureWarehouseStockInitialized(Product $product, Warehouse $warehouse): void
    {
        $hasWarehouseStock = WarehouseStock::query()
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->exists();

        if ($hasWarehouseStock) {
            return;
        }

        if ((float) $product->stock <= 0) {
            return;
        }

        WarehouseStock::query()->updateOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
            ['quantity' => $product->stock]
        );
    }

    private function incrementWarehouseStock(Product $product, Warehouse $warehouse, float $quantity): void
    {
        $stock = WarehouseStock::query()->firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
            ['quantity' => 0]
        );

        $stock->increment('quantity', $quantity);
        $this->syncProductTotalStock($product);
    }

    private function decrementWarehouseStock(Product $product, Warehouse $warehouse, float $quantity): void
    {
        $stock = WarehouseStock::query()->firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
            ['quantity' => 0]
        );

        $stock->decrement('quantity', $quantity);
        $this->syncProductTotalStock($product);
    }
}
