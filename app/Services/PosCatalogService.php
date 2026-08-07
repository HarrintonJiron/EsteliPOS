<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Product;
use App\Models\SaleDetail;
use App\Models\Warehouse;

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
        $product->loadMissing(['baseUnit', 'unitConversions.unit', 'category', 'tax']);

        $warehouseStock = $warehouseId !== null
            ? $product->stockInWarehouse($warehouseId)
            : (float) $product->stock;

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

            $unitStock = $this->units->convertFromBase($product, $warehouseStock, $unit->id);

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
                'stock' => round($warehouseStock, 4),
                'is_default' => true,
            ];
            $defaultUnitId = $product->baseUnit->id;
        }

        $defaultUnit = collect($saleUnits)->firstWhere('is_default', true) ?? $saleUnits[0] ?? null;

        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'sale_price' => $defaultUnit['price'] ?? $this->pricing->resolveUnitPrice($product, $priceListId),
            'stock' => $defaultUnit['stock'] ?? round($warehouseStock, 4),
            'warehouse_stock' => round($warehouseStock, 4),
            'base_unit_id' => $product->base_unit_id,
            'default_unit_id' => $defaultUnitId,
            'default_unit_label' => $defaultUnit['abbreviation'] ?? $product->baseUnitLabel(),
            'sale_units' => $saleUnits,
            'category_id' => $product->category_id,
            'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            'discount_pct' => (float) ($product->discount_pct ?? 0),
            'discount_label' => $product->discount_label,
            'image_url' => $product->getRawOriginal('image_url'),
            'effective_tax_rate' => $product->effectiveTaxRate(),
        ];
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
