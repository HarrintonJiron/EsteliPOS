<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnitConversion;
use App\Models\Unit;
use InvalidArgumentException;

class UnitConversionService
{
    public function convertToBase(Product $product, float $quantity, ?int $unitId = null): float
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('La cantidad no puede ser negativa.');
        }

        if ($unitId === null || $unitId === $product->base_unit_id) {
            return round($quantity, 4);
        }

        $conversion = ProductUnitConversion::query()
            ->where('product_id', $product->id)
            ->where('unit_id', $unitId)
            ->first();

        if ($conversion === null) {
            throw new InvalidArgumentException('Unidad de medida no configurada para este producto.');
        }

        return round($quantity * (float) $conversion->factor_to_base, 4);
    }

    public function convertFromBase(Product $product, float $baseQuantity, int $unitId): float
    {
        if ($unitId === $product->base_unit_id) {
            return round($baseQuantity, 4);
        }

        $conversion = ProductUnitConversion::query()
            ->where('product_id', $product->id)
            ->where('unit_id', $unitId)
            ->first();

        if ($conversion === null || (float) $conversion->factor_to_base == 0.0) {
            throw new InvalidArgumentException('No se puede convertir a la unidad solicitada.');
        }

        return round($baseQuantity / (float) $conversion->factor_to_base, 4);
    }

    /**
     * @return array<int, array{unit: Unit, factor_to_base: float, sale_price: ?float, is_default_sale_unit: bool}>
     */
    public function availableUnitsFor(Product $product): array
    {
        $units = [];

        if ($product->baseUnit) {
            $units[$product->baseUnit->id] = [
                'unit' => $product->baseUnit,
                'factor_to_base' => 1.0,
                'sale_price' => (float) $product->sale_price,
                'is_default_sale_unit' => true,
            ];
        }

        foreach ($product->unitConversions()->with('unit')->get() as $conversion) {
            if ($conversion->unit === null) {
                continue;
            }

            $units[$conversion->unit_id] = [
                'unit' => $conversion->unit,
                'factor_to_base' => (float) $conversion->factor_to_base,
                'sale_price' => $conversion->sale_price !== null ? (float) $conversion->sale_price : null,
                'is_default_sale_unit' => (bool) $conversion->is_default_sale_unit,
            ];
        }

        return $units;
    }

    public function format(float $quantity, ?Unit $unit = null, ?string $fallback = null): string
    {
        $label = $unit?->abbreviation ?? $fallback ?? 'und';

        return rtrim(rtrim(number_format($quantity, 4, '.', ''), '0'), '.').' '.$label;
    }
}
