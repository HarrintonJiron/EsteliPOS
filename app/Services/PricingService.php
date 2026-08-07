<?php

namespace App\Services;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;

class PricingService
{
    public function resolveUnitPrice(Product $product, ?int $priceListId = null, ?int $unitId = null): float
    {
        $priceList = $this->resolvePriceList($priceListId);

        if ($priceList) {
            $itemQuery = PriceListItem::query()
                ->where('price_list_id', $priceList->id)
                ->where('product_id', $product->id)
                ->orderByDesc('min_quantity');

            $item = $unitId
                ? $itemQuery->clone()->where('unit_id', $unitId)->first()
                : $itemQuery->clone()->whereNull('unit_id')->first();

            if ($item === null && $unitId && $unitId === $product->base_unit_id) {
                $item = $itemQuery->clone()->whereNull('unit_id')->first();
            }

            if ($item) {
                return (float) $item->unit_price;
            }
        }

        if ($unitId && $unitId !== $product->base_unit_id) {
            $conversion = $product->unitConversions()->where('unit_id', $unitId)->first();
            if ($conversion?->sale_price !== null) {
                return (float) $conversion->sale_price;
            }
        }

        return $product->effectivePrice();
    }

    public function resolvePriceList(?int $priceListId = null): ?PriceList
    {
        if ($priceListId) {
            $list = PriceList::query()->find($priceListId);

            return $list && $list->isCurrentlyValid() ? $list : null;
        }

        $default = PriceList::default();

        return $default && $default->isCurrentlyValid() ? $default : null;
    }

    public function syncProductToDefaultList(Product $product): void
    {
        $list = PriceList::default();

        if ($list === null) {
            return;
        }

        $this->syncProductToList($product, $list, (float) $product->sale_price);
    }

    public function wholesaleList(): ?PriceList
    {
        return PriceList::query()
            ->where('is_active', true)
            ->where('code', 'MAYOR')
            ->first();
    }

    public function syncProductToList(Product $product, PriceList|int|string $list, float $unitPrice, ?int $unitId = null): void
    {
        $priceList = match (true) {
            $list instanceof PriceList => $list,
            is_int($list) => PriceList::query()->find($list),
            is_string($list) => PriceList::query()->where('code', $list)->first(),
            default => null,
        };

        if ($priceList === null) {
            return;
        }

        PriceListItem::query()->updateOrCreate(
            [
                'price_list_id' => $priceList->id,
                'product_id' => $product->id,
                'unit_id' => $unitId ?? $product->base_unit_id,
            ],
            [
                'unit_price' => $unitPrice,
                'min_quantity' => 1,
            ]
        );
    }
}
