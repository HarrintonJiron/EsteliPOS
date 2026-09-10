<?php

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\ProductUnitConversion;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ExchangeRateService;
use App\Services\PurchaseCostingService;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function purchaseAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    openCashSessionFor($user);

    return $user;
}

test('purchase in usd converts totals and stock using exchange rate', function () {
    $admin = purchaseAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    ExchangeRate::query()->create([
        'from_currency' => 'USD',
        'to_currency' => 'NIO',
        'rate' => 36.5,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'General']);
    $supplier = Supplier::create(['name' => 'Importadora USD', 'status' => 'active']);
    $warehouse = Warehouse::query()->where('is_default', true)->first()
        ?? Warehouse::query()->create(['name' => 'Principal', 'code' => 'MAIN', 'is_default' => true, 'is_active' => true]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Taladro industrial',
        'code' => 'TAL-USD-1',
        'purchase_price' => 0,
        'sale_price' => 2000,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('compras.store'), [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'status' => 'completed',
        'currency' => 'USD',
        'exchange_rate' => 36.5,
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 2,
                'price' => 100,
            ],
        ],
    ])->assertRedirect(route('compras.index'));

    $purchase = Purchase::query()->firstOrFail();

    expect($purchase->currency)->toBe('USD')
        ->and((float) $purchase->exchange_rate)->toBe(36.5)
        ->and((float) $purchase->foreign_total)->toBe(200.0)
        ->and((float) $purchase->total)->toBe(7300.0);

    $product->refresh();

    expect((float) $product->stock)->toBe(2.0)
        ->and((float) $product->purchase_price)->toBe(3650.0);
});

test('purchase with alternate unit converts quantity to base stock', function () {
    $admin = purchaseAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $m3 = Unit::query()->where('abbreviation', 'm3')->firstOrFail();
    $saco = Unit::query()->where('abbreviation', 'saco')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Materiales']);
    $supplier = Supplier::create(['name' => 'Cantera', 'status' => 'active']);
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Arena',
        'code' => 'ARENA-1',
        'purchase_price' => 0,
        'sale_price' => 500,
        'stock' => 0,
        'unit' => 'm3',
        'base_unit_id' => $m3->id,
        'status' => 'active',
    ]);

    ProductUnitConversion::query()->create([
        'product_id' => $product->id,
        'unit_id' => $saco->id,
        'factor_to_base' => 0.04,
        'is_default_sale_unit' => false,
    ]);

    $this->actingAs($admin)->post(route('compras.store'), [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'status' => 'completed',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $saco->id,
                'quantity' => 25,
                'price' => 40,
            ],
        ],
    ])->assertRedirect(route('compras.index'));

    $purchase = Purchase::query()->with('details')->firstOrFail();
    $detail = $purchase->details->first();

    expect((float) $detail->quantity)->toBe(25.0)
        ->and((float) $detail->base_quantity)->toBe(1.0)
        ->and((float) $product->fresh()->stock)->toBe(1.0);
});

test('purchase list shows document cost and currency equivalence', function () {
    $admin = purchaseAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    ExchangeRate::query()->create([
        'from_currency' => 'USD',
        'to_currency' => 'NIO',
        'rate' => 36.5,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'General']);
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplierUsd = Supplier::create(['name' => 'Proveedor USD', 'status' => 'active']);
    $supplierNio = Supplier::create(['name' => 'Proveedor NIO', 'status' => 'active']);

    $productUsd = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto USD',
        'code' => 'EQ-USD-1',
        'purchase_price' => 0,
        'sale_price' => 100,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $productNio = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto NIO',
        'code' => 'EQ-NIO-1',
        'purchase_price' => 0,
        'sale_price' => 100,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('compras.store'), [
        'supplier_id' => $supplierUsd->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'status' => 'completed',
        'currency' => 'USD',
        'exchange_rate' => 36.5,
        'items' => [[
            'product_id' => $productUsd->id,
            'unit_id' => $unit->id,
            'quantity' => 1,
            'price' => 10,
        ]],
    ])->assertRedirect(route('compras.index'));

    $this->actingAs($admin)->post(route('compras.store'), [
        'supplier_id' => $supplierNio->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'status' => 'completed',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $productNio->id,
            'unit_id' => $unit->id,
            'quantity' => 1,
            'price' => 365,
        ]],
    ])->assertRedirect(route('compras.index'));

    $this->actingAs($admin)
        ->get(route('compras.index'))
        ->assertOk()
        ->assertSee('Equivalencia')
        ->assertSee('US$')
        ->assertSee('10.00')
        ->assertSee('365.00')
        ->assertSee('C$');

    $usdPurchase = Purchase::query()->where('currency', 'USD')->firstOrFail();
    $nioPurchase = Purchase::query()->where('currency', 'NIO')->firstOrFail();
    $costing = app(PurchaseCostingService::class);

    expect($costing->presentTotals($usdPurchase)['document_total'])->toBe(10.0)
        ->and($costing->presentTotals($usdPurchase)['equivalence_total'])->toBe(365.0)
        ->and($costing->presentTotals($usdPurchase)['equivalence_currency'])->toBe('NIO')
        ->and($costing->presentTotals($nioPurchase)['document_total'])->toBe(365.0)
        ->and($costing->presentTotals($nioPurchase)['equivalence_total'])->toBe(10.0)
        ->and($costing->presentTotals($nioPurchase)['equivalence_currency'])->toBe('USD');

    $this->actingAs($admin)
        ->get(route('compras.show', $usdPurchase->id))
        ->assertOk()
        ->assertSee('Equivalencia NIO')
        ->assertSee('US$')
        ->assertSee('10.00');
});

test('exchange rate service resolves inverse pairs', function () {
    ExchangeRate::query()->create([
        'from_currency' => 'USD',
        'to_currency' => 'NIO',
        'rate' => 36,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    expect(ExchangeRate::resolveMultiplier('USD', 'NIO'))->toBe(36.0)
        ->and(round((float) ExchangeRate::resolveMultiplier('NIO', 'USD'), 6))->toBe(round(1 / 36, 6))
        ->and(ExchangeRate::convert(2, 'USD', 'NIO'))->toBe(72.0);

    $service = app(ExchangeRateService::class);

    expect($service->resolveMultiplier('USD', 'NIO'))->toBe(36.0)
        ->and($service->convert(2, 'USD', 'NIO'))->toBe(72.0);
});
