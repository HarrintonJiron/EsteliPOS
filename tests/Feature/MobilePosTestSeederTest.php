<?php

use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Database\Seeders\MobilePosTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('mobile POS test data is repeatable and available in every active branch warehouse', function () {
    $warehouses = collect(['Principal', 'Secundaria'])->map(function (string $name, int $index) {
        $warehouse = Warehouse::query()->create([
            'code' => 'TEST-BOD-'.($index + 1),
            'name' => 'Bodega '.$name,
            'is_active' => true,
            'is_default' => $index === 0,
        ]);

        Branch::query()->create([
            'code' => 'TEST-SUC-'.($index + 1),
            'name' => 'Sucursal '.$name,
            'warehouse_id' => $warehouse->id,
            'is_active' => true,
        ]);

        return $warehouse;
    });

    Artisan::call('db:seed', ['--class' => MobilePosTestSeeder::class]);
    Artisan::call('db:seed', ['--class' => MobilePosTestSeeder::class]);

    expect(Product::query()->where('code', 'like', 'TEST-MOV-%')->count())->toBe(12)
        ->and(Client::query()->where('code', 'like', 'TEST-MOV-CLI-%')->count())->toBe(3);

    foreach ($warehouses as $warehouse) {
        expect(WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereHas('product', fn ($query) => $query->where('code', 'like', 'TEST-MOV-%'))
            ->count())->toBe(12);
    }

    $product = Product::query()->where('code', 'TEST-MOV-001')->firstOrFail();
    expect((float) $product->stock)->toBe(60.0)
        ->and((float) $product->warehouseStocks()->sum('quantity'))->toBe(60.0);
});
