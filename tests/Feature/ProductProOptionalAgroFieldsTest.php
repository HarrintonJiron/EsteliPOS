<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseShelf;
use App\Models\WarehouseStock;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('pro product can be created without lot expiry or agrochemical fields', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $category = Category::firstOrCreate(['name' => 'Ferretería']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('inventario.create'))
        ->assertOk()
        ->assertSee('Lote, vencimiento y agroquímicos')
        ->assertSee('aria-label="Categoría"', false)
        ->assertSee('aria-label="Precio de venta"', false)
        ->assertSee('aria-label="Bodega del stock inicial"', false)
        ->assertSee('Opcional');

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Martillo carpintero',
        'code' => 'MART-PRO-1',
        'purchase_price' => 80,
        'sale_price' => 120,
        'stock' => 5,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertRedirect(route('inventario.create'));

    $product = Product::query()->where('code', 'MART-PRO-1')->firstOrFail();

    expect($product->lot)->toBeNull()
        ->and($product->expiry_date)->toBeNull()
        ->and($product->registration_number)->toBeNull()
        ->and($product->active_ingredient)->toBeNull()
        ->and($product->concentration)->toBeNull()
        ->and((float) $product->stock)->toBe(5.0);
});

test('pro and quick product creation can assign initial stock to a warehouse', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $category = Category::firstOrCreate(['name' => 'Ferretería']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('code', 'BOD-02')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('inventario.create'))
        ->assertOk()
        ->assertSee('Existencias por ubicación')
        ->assertSee($warehouse->name);

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Cemento gris',
        'code' => 'CEM-BOD-2',
        'purchase_price' => 300,
        'sale_price' => 380,
        'stock' => 12,
        'base_unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'active',
    ])->assertRedirect(route('inventario.create'));

    $product = Product::query()->where('code', 'CEM-BOD-2')->firstOrFail();

    expect((float) $product->stock)->toBe(12.0)
        ->and((float) WarehouseStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity'))->toBe(12.0);

    $this->actingAs($admin)
        ->get(route('inventario.quick'))
        ->assertOk()
        ->assertSee('Bodega');

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'code' => 'CLAVO-BOD-2',
        'name' => 'Clavo 2"',
        'sale_price' => 10,
        'purchase_price' => 7,
        'stock' => 40,
        'category_id' => $category->id,
        'base_unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
    ])->assertRedirect(route('inventario.index'));

    $quick = Product::query()->where('code', 'CLAVO-BOD-2')->firstOrFail();

    expect((float) WarehouseStock::query()
        ->where('product_id', $quick->id)
        ->where('warehouse_id', $warehouse->id)
        ->value('quantity'))->toBe(40.0);
});

test('pro product creation keeps its warehouse and shelf when initial stock is zero', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $category = Category::firstOrCreate(['name' => 'Ubicaciones']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('code', 'BOD-02')->firstOrFail();
    $shelf = WarehouseShelf::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => 'E-02',
        'name' => 'Estante dos',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Producto sin existencia inicial',
        'code' => 'PRO-UBI-0',
        'purchase_price' => 20,
        'sale_price' => 30,
        'stock' => 0,
        'base_unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'shelf_id' => $shelf->id,
        'status' => 'active',
    ])->assertRedirect(route('inventario.create'))->assertSessionHasNoErrors();

    $product = Product::query()->where('code', 'PRO-UBI-0')->firstOrFail();
    $warehouseStock = WarehouseStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect((float) $warehouseStock->quantity)->toBe(0.0)
        ->and($warehouseStock->aisle)->toBe('E-02');
});

test('quick and pro products distribute initial stock across warehouses and shelves', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);
    $category = Category::firstOrCreate(['name' => 'Distribución múltiple']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouses = Warehouse::query()->where('is_active', true)->orderBy('id')->take(2)->get();
    expect($warehouses)->toHaveCount(2);

    $shelves = $warehouses->map(fn (Warehouse $warehouse, int $index) => WarehouseShelf::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => 'MULTI-'.($index + 1),
        'name' => 'Ubicación '.($index + 1),
        'is_active' => true,
    ]));
    $locations = [
        ['warehouse_id' => $warehouses[0]->id, 'shelf_id' => $shelves[0]->id, 'quantity' => 12],
        ['warehouse_id' => $warehouses[1]->id, 'shelf_id' => $shelves[1]->id, 'quantity' => 8],
    ];

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'code' => 'QUICK-MULTI',
        'name' => 'Producto rápido multiubicación',
        'sale_price' => 30,
        'purchase_price' => 20,
        'category_id' => $category->id,
        'base_unit_id' => $unit->id,
        'locations' => $locations,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Producto Pro multiubicación',
        'code' => 'PRO-MULTI',
        'purchase_price' => 20,
        'sale_price' => 30,
        'base_unit_id' => $unit->id,
        'locations' => $locations,
        'status' => 'active',
    ])->assertRedirect(route('inventario.create'))->assertSessionHasNoErrors();

    foreach (['QUICK-MULTI', 'PRO-MULTI'] as $code) {
        $product = Product::query()->where('code', $code)->firstOrFail();
        expect((float) $product->stock)->toBe(20.0)
            ->and((float) $product->stockInWarehouse($warehouses[0]->id))->toBe(12.0)
            ->and((float) $product->stockInWarehouse($warehouses[1]->id))->toBe(8.0)
            ->and(WarehouseStock::query()->where('product_id', $product->id)->where('warehouse_id', $warehouses[0]->id)->value('aisle'))->toBe('MULTI-1')
            ->and(WarehouseStock::query()->where('product_id', $product->id)->where('warehouse_id', $warehouses[1]->id)->value('aisle'))->toBe('MULTI-2');
    }
});

test('pro product can optionally save agrochemical traceability fields', function () {
    $this->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $category = Category::firstOrCreate(['name' => 'Agroquímicos']);
    $unit = Unit::query()->where('abbreviation', 'lt')->first()
        ?? Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Herbicida total',
        'code' => 'AGRO-PRO-1',
        'purchase_price' => 200,
        'sale_price' => 280,
        'stock' => 10,
        'base_unit_id' => $unit->id,
        'status' => 'active',
        'lot' => 'LOT-2026-01',
        'expiry_date' => now()->addYear()->toDateString(),
        'registration_number' => 'AG-9988',
        'active_ingredient' => 'Glifosato',
        'concentration' => '48% SL',
    ])->assertRedirect(route('inventario.create'));

    $product = Product::query()->where('code', 'AGRO-PRO-1')->firstOrFail();

    expect($product->lot)->toBe('LOT-2026-01')
        ->and($product->registration_number)->toBe('AG-9988')
        ->and($product->active_ingredient)->toBe('Glifosato')
        ->and($product->concentration)->toBe('48% SL')
        ->and(Carbon::parse($product->expiry_date)->toDateString())
        ->toBe(now()->addYear()->toDateString());
});
