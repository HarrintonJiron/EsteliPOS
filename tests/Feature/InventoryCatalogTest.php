<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\ProductUnitConversion;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\UnitConversionService;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function inventoryAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('inventory catalog seeder creates warehouses units and price lists', function () {
    $this->seed(InventoryCatalogSeeder::class);

    expect(Warehouse::query()->count())->toBeGreaterThanOrEqual(3)
        ->and(Unit::query()->where('abbreviation', 'm3')->exists())->toBeTrue()
        ->and(PriceList::query()->where('code', 'GENERAL')->exists())->toBeTrue()
        ->and(PriceList::query()->where('code', 'MAYOR')->exists())->toBeTrue();
});

test('admin can browse inventory hub pages', function () {
    $admin = inventoryAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $this->actingAs($admin)->get(route('inventario.dashboard'))->assertOk()->assertSee('Dashboard de inventario');
    $this->actingAs($admin)->get(route('inventario.warehouses.index'))->assertOk()->assertSee('Bodegas');
    $this->actingAs($admin)->get(route('inventario.price-lists.index'))->assertOk()->assertSee('Listas de precios');
    $this->actingAs($admin)->get(route('inventario.units.index'))->assertOk()->assertSee('Unidades de medida');
});

test('quick product registration can set wholesale price on mayor list', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Ferretería']);

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'code' => 'CLAVO-001',
        'name' => 'Clavo 2 pulgadas',
        'sale_price' => 100,
        'wholesale_price' => 85,
        'purchase_price' => 70,
        'stock' => 50,
        'category_id' => $category->id,
        'unit' => 'unidad',
    ])->assertRedirect(route('inventario.index'));

    $product = Product::query()->where('code', 'CLAVO-001')->firstOrFail();
    $mayorList = PriceList::query()->where('code', 'MAYOR')->firstOrFail();

    expect(PriceListItem::query()
        ->where('price_list_id', $mayorList->id)
        ->where('product_id', $product->id)
        ->value('unit_price'))->toEqual(85.0);
});

test('catalog search updates results while typing', function () {
    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Busqueda']);

    Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Martillo de uña',
        'code' => 'MAR-001',
        'purchase_price' => 80,
        'sale_price' => 120,
        'stock' => 5,
        'unit' => 'unidad',
        'status' => 'active',
    ]);

    Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Destornillador plano',
        'code' => 'DES-001',
        'purchase_price' => 30,
        'sale_price' => 50,
        'stock' => 12,
        'unit' => 'unidad',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('inventario.index', ['live' => 1, 'q' => 'Martillo']), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertSuccessful()
        ->assertSee('Martillo de uña')
        ->assertDontSee('Destornillador plano');
});

test('unit conversion converts sand from cubic meters to sacks', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $m3 = Unit::query()->where('abbreviation', 'm3')->firstOrFail();
    $saco = Unit::query()->where('abbreviation', 'saco')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Materiales']);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Arena fina',
        'code' => 'ARENA-001',
        'purchase_price' => 120,
        'sale_price' => 180,
        'stock' => 10,
        'unit' => 'm3',
        'base_unit_id' => $m3->id,
        'status' => 'active',
    ]);

    ProductUnitConversion::query()->create([
        'product_id' => $product->id,
        'unit_id' => $saco->id,
        'factor_to_base' => 0.04,
        'sale_price' => 450,
    ]);

    $service = app(UnitConversionService::class);
    $baseQty = $service->convertToBase($product, 25, $saco->id);

    expect($baseQty)->toBe(1.0)
        ->and($service->convertFromBase($product, 1, $saco->id))->toBe(25.0);

    $this->actingAs(inventoryAdmin())->postJson(route('inventario.convert'), [
        'product_id' => $product->id,
        'quantity' => 25,
        'from_unit_id' => $saco->id,
        'to_unit_id' => $m3->id,
    ])->assertSuccessful()
        ->assertJsonPath('converted_quantity', 1)
        ->assertJsonPath('base_unit', 'm3');
});

test('pos applies client price list and warehouse stock', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $admin = inventoryAdmin();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $mayorList = PriceList::query()->where('code', 'MAYOR')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Materiales']);

    $client = Client::query()->create([
        'name' => 'Constructor Demo',
        'code' => 'CONS-001',
        'phone' => '88887777',
        'price_list_id' => $mayorList->id,
        'status' => 'active',
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Cemento gris',
        'code' => 'CEM-001',
        'purchase_price' => 200,
        'sale_price' => 280,
        'stock' => 20,
        'unit' => 'saco',
        'base_unit_id' => Unit::query()->where('abbreviation', 'saco')->value('id'),
        'status' => 'active',
    ]);

    PriceListItem::query()->create([
        'price_list_id' => $mayorList->id,
        'product_id' => $product->id,
        'unit_id' => null,
        'unit_price' => 250,
        'min_quantity' => 1,
    ]);

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $response = $this->actingAs($admin)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 2,
            'discount' => 0,
        ]]),
        'amount_received' => 600,
        'order_discount_pct' => 0,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $sale = Sale::query()->with('details')->latest('id')->firstOrFail();
    expect($sale->warehouse_id)->toBe($warehouse->id)
        ->and((float) $sale->details->first()->price)->toBe(250.0);
});

test('pos sale deducts stock from default warehouse for legacy products', function () {
    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Pruebas']);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Clavo 2 pulg',
        'code' => 'CLAVO-001',
        'purchase_price' => 1,
        'sale_price' => 2,
        'stock' => 50,
        'unit' => 'und',
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 2,
            'discount' => 0,
        ]]),
        'amount_received' => 6,
        'order_discount_pct' => 0,
    ])->assertRedirect();

    expect((float) $product->fresh()->stock)->toBe(47.0);
});

test('deleting a zero quantity inventory adjustment succeeds without stock movement errors', function () {
    $this->seed(ConfigurationSeeder::class);

    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Ajustes']);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto conteo exacto',
        'code' => 'CNT-'.str()->random(6),
        'purchase_price' => 10,
        'sale_price' => 20,
        'stock' => 10,
        'unit' => 'und',
        'status' => 'active',
    ]);

    $adjustment = InventoryAdjustment::query()->create([
        'product_id' => $product->id,
        'user_id' => $admin->id,
        'type' => 'count',
        'quantity' => 0,
        'stock_before' => 10,
        'stock_after' => 10,
        'reason' => 'Conteo físico sin diferencia',
    ]);

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('voidForSource')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($admin)
        ->delete(route('ajustes.destroy', $adjustment->id))
        ->assertRedirect(route('ajustes.index'))
        ->assertSessionHas('success');

    expect(InventoryAdjustment::count())->toBe(0)
        ->and(InventoryMovement::count())->toBe(0)
        ->and((float) $product->fresh()->stock)->toBe(10.0);
});
