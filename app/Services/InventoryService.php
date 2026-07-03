<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function stockIn(
        Product $product,
        int $quantity,
        string $reference,
        string $note,
        ?int $userId = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $reference, $note, $userId) {
            $product->increment('stock', $quantity);
            $product->refresh();

            return InventoryMovement::create([
                'product_id' => $product->id,
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
        int $quantity,
        string $reference,
        string $note,
        ?int $userId = null,
        bool $allowNegative = false,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $reference, $note, $userId, $allowNegative) {
            $product->refresh();

            if (! $allowNegative && $product->stock < $quantity) {
                throw new \RuntimeException(
                    "Stock insuficiente para «{$product->name}». Disponible: {$product->stock}"
                );
            }

            $product->decrement('stock', $quantity);
            $product->refresh();

            return InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'stock_after' => $product->stock,
                'reference' => $reference,
                'note' => $note,
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]);
        });
    }

    public function calculatedStock(Product $product): int
    {
        $in = (int) $product->inventoryMovements()->where('type', 'in')->sum('quantity');
        $out = (int) $product->inventoryMovements()->where('type', 'out')->sum('quantity');

        return $in - $out;
    }

    /**
     * @return array{fixed: int, discrepancies: list<array{product: Product, recorded: int, calculated: int}>}
     */
    public function reconcileAll(bool $fix = false): array
    {
        $discrepancies = [];
        $fixed = 0;

        Product::query()->with('inventoryMovements')->chunkById(100, function ($products) use (&$discrepancies, &$fixed, $fix) {
            foreach ($products as $product) {
                $calculated = $this->calculatedStock($product);

                if ($product->stock !== $calculated) {
                    $discrepancies[] = [
                        'product' => $product,
                        'recorded' => $product->stock,
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

    public function salesStatsSubquery(int $days = 30): \Illuminate\Database\Query\Builder
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
            'entries' => (int) InventoryMovement::where('type', 'in')->where('created_at', '>=', $since)->sum('quantity'),
            'exits' => (int) InventoryMovement::where('type', 'out')->where('created_at', '>=', $since)->sum('quantity'),
            'entry_count' => InventoryMovement::where('type', 'in')->where('created_at', '>=', $since)->count(),
            'exit_count' => InventoryMovement::where('type', 'out')->where('created_at', '>=', $since)->count(),
        ];
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
}
