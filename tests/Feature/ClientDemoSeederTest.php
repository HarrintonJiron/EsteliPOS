<?php

use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use Database\Seeders\ClientDemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads a rich ferreteria catalog for client demonstrations', function () {
    $this->seed(ProductionSeeder::class);
    User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->seed(ClientDemoSeeder::class);

    expect(Product::count())->toBeGreaterThan(80)
        ->and(Client::count())->toBeGreaterThan(12)
        ->and(Supplier::count())->toBeGreaterThan(6)
        ->and(Sale::count())->toBeGreaterThan(20)
        ->and(Purchase::count())->toBeGreaterThan(10)
        ->and(CreditPayment::count())->toBeGreaterThan(5)
        ->and(WarehouseStock::count())->toBeGreaterThan(80)
        ->and(Product::query()->whereNotNull('base_unit_id')->count())->toBe(Product::count())
        ->and(Product::query()->where('name', 'Jabón de baño')->exists())->toBeTrue()
        ->and(Product::query()->where('name', 'Jabón de baño')->first()?->unitConversions()->count())->toBe(2)
        ->and(Product::query()->where('image_url', 'like', 'http%')->count())->toBe(0)
        ->and(Sale::query()->whereNotNull('invoice_number')->count())->toBe(Sale::count());
});

it('does not duplicate demonstration data on a second run', function () {
    $this->seed(ProductionSeeder::class);
    User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->seed(ClientDemoSeeder::class);

    $counts = [
        'products' => Product::count(),
        'clients' => Client::count(),
        'sales' => Sale::count(),
        'purchases' => Purchase::count(),
    ];

    $this->seed(ClientDemoSeeder::class);

    expect(Product::count())->toBe($counts['products'])
        ->and(Client::count())->toBe($counts['clients'])
        ->and(Sale::count())->toBe($counts['sales'])
        ->and(Purchase::count())->toBe($counts['purchases']);
});
