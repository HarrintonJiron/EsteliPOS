<?php

namespace App\Services;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;

class PricingService
{
    public function resolveUnitPrice(Product $product, ?int $priceListId = null, ?int $unitId = null, float $quantity = 1): float
    {
        return $this->resolvePrice($product, $priceListId, $unitId, $quantity)['price'];
    }

    /**
     * @return array{price: float, price_list_id: ?int, price_list_item_id: ?int, min_quantity: ?float}
     */
    public function resolvePrice(Product $product, ?int $priceListId = null, ?int $unitId = null, float $quantity = 1): array
    {
        $priceList = $this->resolvePriceList($priceListId);

        if ($priceList) {
            $itemQuery = PriceListItem::query()
                ->where('price_list_id', $priceList->id)
                ->where('product_id', $product->id)
                ->where('min_quantity', '<=', max($quantity, 0.0001))
                ->orderByDesc('min_quantity');

            $item = $unitId
                ? $itemQuery->clone()->where('unit_id', $unitId)->first()
                : $itemQuery->clone()->whereNull('unit_id')->first();

            if ($item === null && $unitId && $unitId === $product->base_unit_id) {
                $item = $itemQuery->clone()->whereNull('unit_id')->first();
            }

            if ($item) {
                return [
                    'price' => (float) $item->unit_price,
                    'price_list_id' => (int) $priceList->id,
                    'price_list_item_id' => (int) $item->id,
                    'min_quantity' => (float) $item->min_quantity,
                ];
            }
        }

        if ($unitId && $unitId !== $product->base_unit_id) {
            $conversion = $product->unitConversions()->where('unit_id', $unitId)->first();
            if ($conversion?->sale_price !== null) {
                return $this->fallbackResult((float) $conversion->sale_price);
            }

            $factor = (float) ($conversion?->factor_to_base ?? 0);
            if ($factor > 0) {
                return $this->fallbackResult(round($product->effectivePrice() * $factor, 2));
            }
        }

        return $this->fallbackResult($product->effectivePrice());
    }

    public function resolvePriceList(?int $priceListId = null): ?PriceList
    {
        if ($priceListId) {
            $list = PriceList::query()->find($priceListId);

            if ($list && $list->isCurrentlyValid()) {
                return $list;
            }
        }

        $default = PriceList::default();

        return $default && $default->isCurrentlyValid() ? $default : null;
    }

    /** @return list<array{min_quantity: float, price: float}> */
    public function priceBreaks(Product $product, ?int $priceListId, ?int $unitId): array
    {
        $priceList = $this->resolvePriceList($priceListId);
        if (! $priceList) {
            return [];
        }

        return PriceListItem::query()
            ->where('price_list_id', $priceList->id)
            ->where('product_id', $product->id)
            ->where(function ($query) use ($product, $unitId) {
                $query->where('unit_id', $unitId);
                if ($unitId && (int) $unitId === (int) $product->base_unit_id) {
                    $query->orWhereNull('unit_id');
                }
            })
            ->orderBy('min_quantity')
            ->get(['min_quantity', 'unit_price'])
            ->map(fn (PriceListItem $item) => [
                'min_quantity' => (float) $item->min_quantity,
                'price' => (float) $item->unit_price,
            ])->all();
    }

    /** @return array{price: float, price_list_id: null, price_list_item_id: null, min_quantity: null} */
    private function fallbackResult(float $price): array
    {
        return ['price' => $price, 'price_list_id' => null, 'price_list_item_id' => null, 'min_quantity' => null];
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
                'min_quantity' => 1,
            ],
            [
                'unit_price' => $unitPrice,
            ]
        );
    }
}
