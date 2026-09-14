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
use App\Models\WarehouseShelf;
use App\Models\WarehouseStock;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use App\Services\PricingService;
use App\Services\PurchaseCostingService;
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

    openCashSessionFor($user);

    return $user;
}

test('inventory catalog seeder creates warehouses units and price lists', function () {
    $this->seed(InventoryCatalogSeeder::class);

    expect(Warehouse::query()->count())->toBeGreaterThanOrEqual(3)
        ->and(Unit::query()->where('abbreviation', 'm3')->exists())->toBeTrue()
        ->and(Unit::query()->where('abbreviation', 'ristra')->exists())->toBeTrue()
        ->and(Unit::query()->where('abbreviation', 'caja')->exists())->toBeTrue()
        ->and(PriceList::query()->where('code', 'GENERAL')->exists())->toBeTrue()
        ->and(PriceList::query()->where('code', 'MAYOR')->exists())->toBeTrue();
});

test('admin can browse inventory hub pages', function () {
    $admin = inventoryAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $this->actingAs($admin)->get(route('inventario.dashboard'))->assertOk()->assertSee('Dashboard de inventario');
    $this->actingAs($admin)->get(route('inventario.warehouses.index'))->assertOk()->assertSee('Bodegas');
    $this->actingAs($admin)->get(route('inventario.price-lists.index'))->assertOk()->assertSee('Listas de precios');
    $priceList = PriceList::query()->where('code', 'GENERAL')->firstOrFail();
    $this->actingAs($admin)->get(route('inventario.price-lists.show', $priceList))->assertOk()->assertSee('Guardar escala');
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

test('quick product registration can create its first presentation', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Abarrotes rápidos']);
    $und = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $caja = Unit::query()->where('abbreviation', 'caja')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('inventario.quick'))
        ->assertOk()
        ->assertSee('Agregar caja, ristra u otra presentación');

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'code' => 'RAP-CAJA-001',
        'name' => 'Jabón rápido en caja',
        'sale_price' => 8,
        'purchase_price' => 5,
        'stock' => 72,
        'category_id' => $category->id,
        'base_unit_id' => $und->id,
        'presentation_unit_id' => $caja->id,
        'presentation_quantity' => 36,
        'presentation_sale_price' => 270,
        'presentation_barcode' => '7441000000369',
        'presentation_use_for_purchase' => 1,
        'presentation_use_for_sale' => 1,
        'presentation_default_purchase' => 1,
    ])->assertRedirect(route('inventario.index'))->assertSessionHasNoErrors();

    $product = Product::query()->where('code', 'RAP-CAJA-001')->firstOrFail();
    $conversion = $product->unitConversions()->firstOrFail();

    expect((int) $conversion->unit_id)->toBe($caja->id)
        ->and((int) $conversion->equals_unit_id)->toBe($und->id)
        ->and((float) $conversion->factor_to_base)->toBe(36.0)
        ->and((float) $conversion->sale_price)->toBe(270.0)
        ->and($conversion->barcode)->toBe('7441000000369')
        ->and($conversion->is_default_purchase_unit)->toBeTrue()
        ->and((float) $product->fresh()->stock)->toBe(72.0);
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

test('product conversion can express one carga equals two quintales', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();

    $qq = Unit::query()->where('abbreviation', 'qq')->firstOrFail();
    $carga = Unit::query()->where('abbreviation', 'carga')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Granos']);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Maíz seco',
        'code' => 'MAIZ-QQ-1',
        'purchase_price' => 1200,
        'sale_price' => 1500,
        'stock' => 0,
        'unit' => 'qq',
        'base_unit_id' => $qq->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $carga->id,
        'equals_base_qty' => 2,
        'sale_price' => 2900,
        'is_default_sale_unit' => 1,
    ])->assertRedirect()
        ->assertSessionHas('success');

    $conversion = ProductUnitConversion::query()
        ->where('product_id', $product->id)
        ->where('unit_id', $carga->id)
        ->firstOrFail();

    expect((float) $conversion->factor_to_base)->toBe(2.0)
        ->and((int) $conversion->equals_unit_id)->toBe($qq->id)
        ->and((float) $conversion->equals_quantity)->toBe(2.0)
        ->and((bool) $conversion->is_default_sale_unit)->toBeTrue();

    $service = app(UnitConversionService::class);

    expect($service->convertToBase($product, 3, $carga->id))->toBe(6.0)
        ->and($service->convertFromBase($product, 6, $carga->id))->toBe(3.0);

    $this->actingAs($admin)
        ->get(route('inventario.show', $product->id))
        ->assertOk()
        ->assertSee('Cómo se vende')
        ->assertSee('Unidades y conversiones')
        ->assertSee('Opcional')
        ->assertSee('1 carga')
        ->assertSee('2 qq');
});

test('soap can be bought in boxes and sold as ristras without duplicating the product', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();

    $und = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $ristra = Unit::query()->where('abbreviation', 'ristra')->firstOrFail();
    $caja = Unit::query()->where('abbreviation', 'caja')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Limpieza']);
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Jabón de baño',
        'code' => 'JABON-001',
        'purchase_price' => 5.5,
        'sale_price' => 8,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $und->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $ristra->id,
        'equals_base_qty' => 3,
        'equals_unit_id' => $und->id,
        'is_default_sale_unit' => 1,
    ])->assertRedirect()->assertSessionHas('success');

    $this->actingAs($admin)
        ->get(route('inventario.show', $product->id).'?conversiones=1')
        ->assertOk()
        ->assertSee('Cómo se vende')
        ->assertSee('Agregar otra presentación')
        ->assertSee('contiene')
        ->assertSee('1 ristra')
        ->assertSee('Predeterminada');

    $this->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $caja->id,
        'equals_base_qty' => 12,
        'equals_unit_id' => $ristra->id,
        'sale_price' => 270,
    ])->assertRedirect()->assertSessionHas('success');

    $product->refresh()->load('unitConversions');
    $ristraFactor = (float) $product->unitConversions->firstWhere('unit_id', $ristra->id)?->factor_to_base;
    $cajaFactor = (float) $product->unitConversions->firstWhere('unit_id', $caja->id)?->factor_to_base;

    expect($ristraFactor)->toBe(3.0)
        ->and($cajaFactor)->toBe(36.0);

    $units = app(UnitConversionService::class);
    $pricing = app(PricingService::class);
    $catalog = app(PosCatalogService::class);

    expect($units->convertToBase($product, 1, $ristra->id))->toBe(3.0)
        ->and($units->convertToBase($product, 1, $caja->id))->toBe(36.0)
        ->and($pricing->resolveUnitPrice($product, null, $ristra->id))->toBe(24.0)
        ->and($pricing->resolveUnitPrice($product, null, $caja->id))->toBe(270.0);

    $available = $units->availableUnitsFor($product);
    expect($available[$ristra->id]['is_default_sale_unit'])->toBeTrue()
        ->and($available[$und->id]['is_default_sale_unit'])->toBeFalse();

    app(InventoryService::class)->stockIn(
        $product,
        72,
        'test-in',
        'Compra de 2 cajas',
        $admin->id,
        $warehouse->id,
    );

    $serialized = $catalog->serializeProduct($product->fresh(['baseUnit', 'unitConversions.unit', 'warehouseStocks.warehouse']), $warehouse->id);
    $defaultUnits = collect($serialized['sale_units'])->where('is_default', true);

    expect($serialized['default_unit_id'])->toBe($ristra->id)
        ->and($serialized['base_unit_label'])->toBe('und')
        ->and((float) $serialized['stock'])->toBe(72.0)
        ->and($defaultUnits)->toHaveCount(1)
        ->and($defaultUnits->first()['abbreviation'])->toBe('ristra')
        ->and((float) $defaultUnits->first()['price'])->toBe(24.0)
        ->and((float) $defaultUnits->first()['stock'])->toBe(24.0);

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($admin)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'warehouse_id' => $warehouse->id,
        'items' => json_encode([[
            'product_id' => $product->id,
            'unit_id' => $ristra->id,
            'quantity' => 2,
            'discount' => 0,
        ]]),
        'amount_received' => 100,
        'order_discount_pct' => 0,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->with('details')->latest('id')->firstOrFail();

    expect((float) $sale->details->first()->quantity)->toBe(2.0)
        ->and((int) $sale->details->first()->unit_id)->toBe($ristra->id)
        ->and((float) $sale->details->first()->price)->toBe(24.0)
        ->and((float) $product->fresh()->stock)->toBe(66.0);
});

test('the default sale unit can be changed for the pos', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();

    $und = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $ristra = Unit::query()->where('abbreviation', 'ristra')->firstOrFail();
    $caja = Unit::query()->where('abbreviation', 'caja')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Limpieza']);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Jabón Zote',
        'code' => 'JABON-DEF-001',
        'purchase_price' => 5.5,
        'sale_price' => 8,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $und->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $ristra->id,
        'equals_base_qty' => 3,
        'equals_unit_id' => $und->id,
        'is_default_sale_unit' => 1,
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $caja->id,
        'equals_base_qty' => 12,
        'equals_unit_id' => $ristra->id,
        'sale_price' => 270,
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('inventario.conversions.default', $product->id), [
        'unit_id' => $caja->id,
    ])->assertRedirect()->assertSessionHas('success');

    $product->refresh()->load('unitConversions');
    $catalog = app(PosCatalogService::class)->serializeProduct($product);

    expect($product->unitConversions->firstWhere('unit_id', $caja->id)?->is_default_sale_unit)->toBeTrue()
        ->and($product->unitConversions->firstWhere('unit_id', $ristra->id)?->is_default_sale_unit)->toBeFalse()
        ->and($catalog['default_unit_id'])->toBe($caja->id)
        ->and(collect($catalog['sale_units'])->where('is_default', true)->pluck('abbreviation')->all())->toBe(['caja'])
        ->and(collect($catalog['sale_units'])->pluck('abbreviation')->sort()->values()->all())->toContain('und', 'ristra', 'caja');

    $this->actingAs($admin)
        ->get(route('inventario.show', $product->id).'?conversiones=1')
        ->assertOk()
        ->assertSee('Predeterminada')
        ->assertSee('Usar al vender');

    $this->actingAs($admin)
        ->getJson(route('facturacion.pos-products', ['search' => 'JABON-DEF-001']))
        ->assertOk()
        ->assertJsonPath('0.code', 'JABON-DEF-001')
        ->assertJsonPath('0.default_unit_id', $caja->id)
        ->assertJsonPath('0.default_unit_label', 'caja');

    $pos = $this->actingAs($admin)->get(route('facturacion.pos'));
    $pos->assertOk()
        ->assertSee('data-product-unit-select', false)
        ->assertSee('JABON-DEF-001', false);

    $posProduct = collect($pos->viewData('products'))->firstWhere('code', 'JABON-DEF-001');
    expect($posProduct['sale_units'])->toHaveCount(3)
        ->and($posProduct['default_unit_id'])->toBe($caja->id);

    $this->actingAs($admin)->post(route('inventario.conversions.default', $product->id), [
        'unit_id' => $und->id,
    ])->assertRedirect()->assertSessionHas('success');

    $product->refresh()->load('unitConversions');
    $catalog = app(PosCatalogService::class)->serializeProduct($product);

    expect($product->unitConversions->contains('is_default_sale_unit', true))->toBeFalse()
        ->and($catalog['default_unit_id'])->toBe($und->id);
});

test('scanning a presentation barcode selects that presentation in the pos', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $und = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $caja = Unit::query()->where('abbreviation', 'caja')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Abarrotes']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Aceite en caja',
        'code' => 'ACEITE-CAJA',
        'purchase_price' => 40,
        'sale_price' => 50,
        'stock' => 120,
        'unit' => 'und',
        'base_unit_id' => $und->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $caja->id,
        'equals_base_qty' => 12,
        'equals_unit_id' => $und->id,
        'sale_price' => 570,
        'barcode' => '7441000000123',
        'usage_options' => 1,
        'use_for_purchase' => 1,
        'use_for_sale' => 1,
        'is_default_purchase_unit' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $purchaseUnits = app(PurchaseCostingService::class)->purchaseUnitsFor(
        $product->fresh(['baseUnit', 'unitConversions.unit'])
    );

    expect($purchaseUnits[0]['id'])->toBe($caja->id)
        ->and($purchaseUnits[0]['is_default_purchase_unit'])->toBeTrue();

    $this->actingAs($admin)
        ->getJson(route('facturacion.pos-products', ['search' => '7441000000123']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.code', 'ACEITE-CAJA')
        ->assertJsonPath('0.default_unit_id', $caja->id)
        ->assertJsonPath('0.default_unit_label', 'caja');
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
        ->and((float) $sale->details->first()->price)->toBe(250.0)
        ->and($sale->price_list_id)->toBe($mayorList->id)
        ->and($sale->price_list_name)->toBe($mayorList->name)
        ->and($sale->details->first()->price_list_item_id)->not->toBeNull()
        ->and((float) $sale->details->first()->price_min_quantity)->toBe(1.0);
});

test('price lists apply the highest eligible quantity tier', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $list = PriceList::query()->where('code', 'MAYOR')->firstOrFail();
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Abarrotes']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Jabón por unidad',
        'code' => 'JAB-TIER-001',
        'purchase_price' => 6,
        'sale_price' => 12,
        'stock' => 100,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    foreach ([[1, 12], [6, 11], [12, 10]] as [$minimum, $price]) {
        PriceListItem::query()->create([
            'price_list_id' => $list->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'min_quantity' => $minimum,
            'unit_price' => $price,
        ]);
    }

    $pricing = app(PricingService::class);

    expect($pricing->resolveUnitPrice($product, $list->id, $unit->id, 1))->toBe(12.0)
        ->and($pricing->resolveUnitPrice($product, $list->id, $unit->id, 5))->toBe(12.0)
        ->and($pricing->resolveUnitPrice($product, $list->id, $unit->id, 6))->toBe(11.0)
        ->and($pricing->resolveUnitPrice($product, $list->id, $unit->id, 11))->toBe(11.0)
        ->and($pricing->resolveUnitPrice($product, $list->id, $unit->id, 12))->toBe(10.0);
});

test('expired assigned price list falls back to the valid default list', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $default = PriceList::query()->where('code', 'GENERAL')->firstOrFail();
    $expired = PriceList::query()->where('code', 'MAYOR')->firstOrFail();
    $expired->update(['valid_to' => now()->subDay()]);

    expect(app(PricingService::class)->resolvePriceList($expired->id)?->id)->toBe($default->id);
});

test('pos shows product available when stock is in another warehouse', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Pruebas Bodega']);
    $main = Warehouse::query()->where('is_default', true)->firstOrFail();
    $secondary = Warehouse::query()->where('is_default', false)->where('is_active', true)->firstOrFail();

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Cemento Gris',
        'code' => 'CEM-AUTO-001',
        'purchase_price' => 280,
        'sale_price' => 320,
        'stock' => 0,
        'unit' => 'saco',
        'status' => 'active',
    ]);

    app(InventoryService::class)->stockIn(
        $product,
        10,
        'test-in',
        'Entrada a bodega secundaria',
        $admin->id,
        $secondary->id,
    );

    $response = $this->actingAs($admin)->getJson(route('facturacion.pos-products', [
        'search' => 'CEM-AUTO-001',
    ]));

    $response->assertOk();
    $payload = collect($response->json())->firstWhere('code', 'CEM-AUTO-001');

    expect($payload)->not->toBeNull()
        ->and((float) $payload['total_stock'])->toBe(10.0)
        ->and((float) $payload['stock'])->toBeGreaterThan(0)
        ->and((int) $payload['preferred_warehouse_id'])->toBe($secondary->id);

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($admin)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'warehouse_id' => null,
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 2,
            'discount' => 0,
        ]]),
        'amount_received' => 640,
        'order_discount_pct' => 0,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect((float) $product->fresh()->stock)->toBe(8.0)
        ->and((float) $product->stockInWarehouse($secondary->id))->toBe(8.0)
        ->and((float) $product->stockInWarehouse($main->id))->toBe(0.0);
});

test('pos warehouse filter only returns products stocked in the selected warehouse', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Filtro POS por bodega']);
    $main = Warehouse::query()->where('is_default', true)->firstOrFail();
    $secondary = Warehouse::query()->where('is_default', false)->where('is_active', true)->firstOrFail();

    $mainProduct = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto bodega principal',
        'code' => 'POS-BOD-MAIN',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
    ]);
    $secondaryProduct = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto bodega secundaria',
        'code' => 'POS-BOD-SECONDARY',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
    ]);

    app(InventoryService::class)->stockIn($mainProduct, 5, 'test', 'Stock principal', $admin->id, $main->id);
    app(InventoryService::class)->stockIn($secondaryProduct, 7, 'test', 'Stock secundario', $admin->id, $secondary->id);

    $response = $this->actingAs($admin)->getJson(route('facturacion.pos-products', [
        'warehouse_id' => $secondary->id,
        'category_id' => $category->id,
    ]));

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.code', 'POS-BOD-SECONDARY')
        ->assertJsonPath('0.warehouse_stock', 7)
        ->assertJsonPath('0.stock', 7);
});

test('warehouse transfer moves stock between locations', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $category = Category::firstOrCreate(['name' => 'Transferencias']);
    $from = Warehouse::query()->where('is_default', true)->firstOrFail();
    $to = Warehouse::query()->where('is_default', false)->where('is_active', true)->firstOrFail();

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Varilla 3/8',
        'code' => 'VAR-TRF-001',
        'purchase_price' => 50,
        'sale_price' => 75,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
    ]);

    app(InventoryService::class)->stockIn(
        $product,
        20,
        'seed',
        'Carga inicial',
        $admin->id,
        $from->id,
    );

    $this->actingAs($admin)->post(route('inventario.warehouses.transfer', $from), [
        'product_id' => $product->id,
        'to_warehouse_id' => $to->id,
        'quantity' => 5,
        'note' => 'Movimiento a patio',
    ])->assertRedirect()->assertSessionHas('success');

    expect((float) $product->fresh()->stock)->toBe(20.0)
        ->and((float) $product->stockInWarehouse($from->id))->toBe(15.0)
        ->and((float) $product->stockInWarehouse($to->id))->toBe(5.0);
});

test('warehouse can create shelves and quick registration keeps the location with zero stock', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Productos con estante']);

    $this->actingAs($admin)->post(route('inventario.warehouses.shelves.store', $warehouse), [
        'code' => 'A-01',
        'name' => 'Fertilizantes',
    ])->assertRedirect()->assertSessionHas('success');

    $shelf = WarehouseShelf::query()->where('warehouse_id', $warehouse->id)->where('code', 'A-01')->firstOrFail();

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'code' => 'EST-001',
        'name' => 'Producto en estante',
        'sale_price' => 150,
        'purchase_price' => 100,
        'stock' => 0,
        'category_id' => $category->id,
        'warehouse_id' => $warehouse->id,
        'shelf_id' => $shelf->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $product = Product::query()->where('code', 'EST-001')->firstOrFail();
    $warehouseStock = WarehouseStock::query()
        ->where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($warehouseStock->aisle)->toBe('A-01')
        ->and((float) $warehouseStock->quantity)->toBe(0.0);
});

test('quick and pro forms can create warehouses and shelves inline', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();

    $this->actingAs($admin)->get(route('inventario.quick'))
        ->assertOk()
        ->assertSee('Agregar nueva bodega')
        ->assertSee('Agregar nuevo estante')
        ->assertDontSee('Editar bodega')
        ->assertDontSee('Editar estante');

    $this->actingAs($admin)->get(route('inventario.create'))
        ->assertOk()
        ->assertSee('Agregar nueva bodega')
        ->assertSee('Agregar nuevo estante')
        ->assertDontSee('Editar bodega')
        ->assertDontSee('Editar estante');

    $warehouseResponse = $this->actingAs($admin)->postJson(route('inventario.warehouses.store'), [
        'code' => 'BOD-INLINE',
        'name' => 'Bodega creada en producto',
    ]);

    $warehouseResponse->assertCreated()
        ->assertJsonPath('warehouse.code', 'BOD-INLINE')
        ->assertJsonPath('warehouse.name', 'Bodega creada en producto');

    $warehouse = Warehouse::query()->where('code', 'BOD-INLINE')->firstOrFail();
    $this->actingAs($admin)->postJson(route('inventario.warehouses.shelves.store', $warehouse), [
        'code' => 'Z-01',
        'name' => 'Estante inmediato',
    ])->assertCreated()
        ->assertJsonPath('shelf.warehouse_id', $warehouse->id)
        ->assertJsonPath('shelf.label', 'Z-01 · Estante inmediato');

    $this->actingAs($admin)->putJson(route('inventario.warehouses.update', $warehouse), [
        'code' => 'BOD-INLINE-EDIT',
        'name' => 'Bodega editada en producto',
    ])->assertOk()
        ->assertJsonPath('warehouse.code', 'BOD-INLINE-EDIT')
        ->assertJsonPath('warehouse.name', 'Bodega editada en producto');

    $shelf = WarehouseShelf::query()->where('warehouse_id', $warehouse->id)->where('code', 'Z-01')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Edición de estantes']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto ubicado antes de editar',
        'code' => 'SHELF-EDIT-001',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
    ]);
    WarehouseStock::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 0,
        'aisle' => 'Z-01',
    ]);

    $this->actingAs($admin)->putJson(route('inventario.warehouses.shelves.update', [$warehouse, $shelf]), [
        'code' => 'Z-02',
        'name' => 'Estante editado',
    ])->assertOk()
        ->assertJsonPath('shelf.label', 'Z-02 · Estante editado');

    expect(WarehouseStock::query()->where('product_id', $product->id)->value('aisle'))->toBe('Z-02');
});

test('shelf module can create browse inspect and edit shelf records', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = inventoryAdmin();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();

    $this->actingAs($admin)->get(route('inventario.shelves.index'))
        ->assertOk()
        ->assertSee('Estantes')
        ->assertSee(route('inventario.shelves.index'), false)
        ->assertSee('Agregar nuevo estante');

    $this->actingAs($admin)->post(route('inventario.shelves.store'), [
        'warehouse_id' => $warehouse->id,
        'code' => 'MOD-01',
        'name' => 'Estante del módulo',
    ])->assertRedirect();

    $shelf = WarehouseShelf::query()->where('warehouse_id', $warehouse->id)->where('code', 'MOD-01')->firstOrFail();
    $this->actingAs($admin)->get(route('inventario.shelves.show', $shelf))
        ->assertOk()
        ->assertSee('MOD-01')
        ->assertSee('Productos registrados');
    $this->actingAs($admin)->get(route('inventario.shelves.edit', $shelf))
        ->assertOk()
        ->assertSee('Editar estante');

    $this->actingAs($admin)->put(route('inventario.shelves.update', $shelf), [
        'warehouse_id' => $warehouse->id,
        'code' => 'MOD-02',
        'name' => 'Estante actualizado',
        'is_active' => true,
    ])->assertRedirect(route('inventario.shelves.show', $shelf));

    expect($shelf->fresh()->code)->toBe('MOD-02')
        ->and($shelf->fresh()->name)->toBe('Estante actualizado');
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
