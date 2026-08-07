<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Product;
use App\Models\SaleDetail;
use App\Models\Warehouse;
use App\Models\WarehouseStock;

class PosCatalogService
{
    public function __construct(
        private PricingService $pricing,
        private UnitConversionService $units,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serializeProduct(Product $product, ?int $warehouseId = null, ?int $priceListId = null): array
    {
        $product->loadMissing(['baseUnit', 'unitConversions.unit', 'category', 'tax', 'warehouseStocks.warehouse']);

        $totalStock = (float) $product->stock;
        $stocksByWarehouse = $this->stocksByWarehouse($product);
        $preferredWarehouseId = $this->preferredWarehouseId($product, $warehouseId);

        $displayWarehouseId = $warehouseId;
        $warehouseStock = $displayWarehouseId !== null
            ? $product->stockInWarehouse($displayWarehouseId)
            : $totalStock;

        // Si la bodega elegida no tiene stock pero sí hay en otra, mostrar el stock usable.
        $sellableStock = $totalStock > 0
            ? ($warehouseStock > 0 ? $warehouseStock : $totalStock)
            : 0.0;

        $saleUnits = [];
        $defaultUnitId = $product->base_unit_id;

        foreach ($this->units->availableUnitsFor($product) as $entry) {
            $unit = $entry['unit'];
            $isDefault = (bool) ($entry['is_default_sale_unit'] ?? false)
                || ($defaultUnitId === null && count($saleUnits) === 0)
                || $unit->id === $defaultUnitId;

            if ($isDefault) {
                $defaultUnitId = $unit->id;
            }

            $unitStock = $this->units->convertFromBase($product, $sellableStock, $unit->id);

            $saleUnits[] = [
                'id' => $unit->id,
                'abbreviation' => $unit->abbreviation,
                'name' => $unit->name,
                'price' => $this->pricing->resolveUnitPrice($product, $priceListId, $unit->id),
                'stock' => round($unitStock, 4),
                'is_default' => $isDefault,
            ];
        }

        if ($saleUnits === [] && $product->baseUnit) {
            $saleUnits[] = [
                'id' => $product->baseUnit->id,
                'abbreviation' => $product->baseUnit->abbreviation,
                'name' => $product->baseUnit->name,
                'price' => $this->pricing->resolveUnitPrice($product, $priceListId, $product->baseUnit->id),
                'stock' => round($sellableStock, 4),
                'is_default' => true,
            ];
            $defaultUnitId = $product->baseUnit->id;
        }

        $defaultUnit = collect($saleUnits)->firstWhere('is_default', true) ?? $saleUnits[0] ?? null;
        $preferredWarehouseName = collect($stocksByWarehouse)
            ->firstWhere('id', $preferredWarehouseId)['name'] ?? null;

        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'sale_price' => $defaultUnit['price'] ?? $this->pricing->resolveUnitPrice($product, $priceListId),
            'stock' => $defaultUnit['stock'] ?? round($sellableStock, 4),
            'total_stock' => round($totalStock, 4),
            'warehouse_stock' => round($warehouseStock, 4),
            'preferred_warehouse_id' => $preferredWarehouseId,
            'preferred_warehouse_name' => $preferredWarehouseName,
            'stocks_by_warehouse' => $stocksByWarehouse,
            'base_unit_id' => $product->base_unit_id,
            'default_unit_id' => $defaultUnitId,
            'default_unit_label' => $defaultUnit['abbreviation'] ?? $product->baseUnitLabel(),
            'sale_units' => $saleUnits,
            'category_id' => $product->category_id,
            'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            'discount_pct' => (float) ($product->discount_pct ?? 0),
            'discount_label' => $product->discount_label,
            'image_url' => $product->image_url,
            'effective_tax_rate' => $product->effectiveTaxRate(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, code: string, quantity: float}>
     */
    public function stocksByWarehouse(Product $product): array
    {
        $rows = $product->relationLoaded('warehouseStocks')
            ? $product->warehouseStocks
            : $product->warehouseStocks()->with('warehouse')->get();

        return $rows
            ->filter(fn (WarehouseStock $stock) => (float) $stock->quantity > 0 && $stock->warehouse)
            ->sortByDesc(fn (WarehouseStock $stock) => (float) $stock->quantity)
            ->values()
            ->map(fn (WarehouseStock $stock) => [
                'id' => (int) $stock->warehouse_id,
                'name' => $stock->warehouse->name,
                'code' => $stock->warehouse->code,
                'quantity' => round((float) $stock->quantity, 4),
            ])
            ->all();
    }

    public function preferredWarehouseId(Product $product, ?int $preferredWarehouseId = null, float $quantity = 0.0001): ?int
    {
        if ($preferredWarehouseId) {
            $inPreferred = $product->stockInWarehouse($preferredWarehouseId);
            if ($inPreferred >= $quantity) {
                return $preferredWarehouseId;
            }
        }

        $best = WarehouseStock::query()
            ->where('product_id', $product->id)
            ->where('quantity', '>=', $quantity)
            ->orderByDesc('quantity')
            ->value('warehouse_id');

        if ($best) {
            return (int) $best;
        }

        if ((float) $product->stock > 0) {
            return $this->resolveWarehouseId($preferredWarehouseId);
        }

        return $preferredWarehouseId ?: $this->resolveWarehouseId(null);
    }

    /**
     * Resuelve de qué bodega salir para una cantidad. Si la preferida no alcanza,
     * usa automáticamente otra con stock suficiente.
     */
    public function resolveWarehouseForQuantity(Product $product, float $quantity, ?int $preferredWarehouseId = null): int
    {
        if ($quantity <= 0) {
            return $this->resolveWarehouseId($preferredWarehouseId);
        }

        if ($preferredWarehouseId) {
            $available = $product->stockInWarehouse($preferredWarehouseId);
            if ($available >= $quantity) {
                return $preferredWarehouseId;
            }
        }

        $warehouseId = WarehouseStock::query()
            ->where('product_id', $product->id)
            ->where('quantity', '>=', $quantity)
            ->orderByDesc('quantity')
            ->value('warehouse_id');

        if ($warehouseId) {
            return (int) $warehouseId;
        }

        // Legacy: producto con stock total pero sin filas de bodega
        if ((float) $product->stock >= $quantity) {
            return $this->resolveWarehouseId($preferredWarehouseId);
        }

        throw new \RuntimeException(
            "Stock insuficiente para «{$product->name}». Disponible total: {$product->stock}"
        );
    }

    /**
     * @return array{unit_id: ?int, quantity: float, base_quantity: float, price: float}
     */
    public function resolveSaleLine(Product $product, float $quantity, ?int $unitId, ?int $priceListId): array
    {
        $resolvedUnitId = $unitId ?: $product->base_unit_id;
        $baseQuantity = $this->units->convertToBase($product, $quantity, $resolvedUnitId);

        return [
            'unit_id' => $resolvedUnitId,
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'price' => $this->pricing->resolveUnitPrice($product, $priceListId, $resolvedUnitId),
        ];
    }

    public function resolveWarehouseId(?int $warehouseId): int
    {
        if ($warehouseId) {
            $warehouse = Warehouse::query()->where('is_active', true)->find($warehouseId);
            if ($warehouse) {
                return $warehouse->id;
            }
        }

        return Warehouse::default()?->id
            ?? Warehouse::query()->create([
                'code' => 'BOD-01',
                'name' => 'Bodega Principal',
                'is_default' => true,
                'is_active' => true,
            ])->id;
    }

    public function resolvePriceListId(?int $clientId): ?int
    {
        if (! $clientId) {
            return null;
        }

        $client = Client::query()->find($clientId);

        return $client?->price_list_id;
    }

    public function saleDetailBaseQuantity(SaleDetail $detail): float
    {
        $product = $detail->relationLoaded('product') ? $detail->product : Product::query()->find($detail->product_id);

        if ($product === null) {
            return (float) $detail->quantity;
        }

        if ($detail->unit_id) {
            return $this->units->convertToBase($product, (float) $detail->quantity, (int) $detail->unit_id);
        }

        return (float) $detail->quantity;
    }
}
