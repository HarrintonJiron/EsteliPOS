<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function codeGenAdmin(): User
{
    test()->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    return $admin;
}

test('creating a product with the pro form and no code auto-generates one', function () {
    $admin = codeGenAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Producto sin codigo',
        'purchase_price' => 50,
        'sale_price' => 75,
        'stock' => 1,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertRedirect(route('inventario.create'))->assertSessionHasNoErrors();

    $product = Product::query()->where('name', 'Producto sin codigo')->firstOrFail();

    expect($product->code)->not->toBeEmpty()
        ->and($product->code)->toStartWith('PROD-');
});

test('creating two products without a code in a row never repeats the generated code', function () {
    $admin = codeGenAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    foreach (['Producto A', 'Producto B'] as $name) {
        $this->actingAs($admin)->post(route('inventario.store'), [
            'category_id' => $category->id,
            'name' => $name,
            'purchase_price' => 10,
            'sale_price' => 15,
            'stock' => 1,
            'base_unit_id' => $unit->id,
            'status' => 'active',
        ])->assertSessionHasNoErrors();
    }

    $codes = Product::query()->whereIn('name', ['Producto A', 'Producto B'])->pluck('code');

    expect($codes->count())->toBe(2)
        ->and($codes->unique()->count())->toBe(2);
});

test('the pro form still respects a code typed manually, such as a phone IMEI', function () {
    $admin = codeGenAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'iPhone con IMEI',
        'code' => '350000000000123',
        'purchase_price' => 18000,
        'sale_price' => 22000,
        'stock' => 1,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $product = Product::query()->where('name', 'iPhone con IMEI')->firstOrFail();

    expect($product->code)->toBe('350000000000123');
});

test('registro rapido creates a product with an auto-generated code when left blank', function () {
    $admin = codeGenAdmin();
    Category::firstOrCreate(['name' => 'Celulares']);

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'name' => 'Producto rapido sin codigo',
        'sale_price' => 40,
    ])->assertSessionHasNoErrors();

    $product = Product::query()->where('name', 'Producto rapido sin codigo')->firstOrFail();

    expect($product->code)->not->toBeEmpty()
        ->and($product->code)->toStartWith('PROD-');
});

test('auto-generated codes skip numbers used by soft-deleted products', function () {
    $admin = codeGenAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $service = app(App\Services\InventoryService::class);
    $takenCode = $service->nextProductCode();

    $deleted = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto luego eliminado',
        'code' => $takenCode,
        'purchase_price' => 1,
        'sale_price' => 2,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
    ]);
    $deleted->delete();

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Producto tras borrado',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 1,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertRedirect(route('inventario.create'))->assertSessionHasNoErrors();

    $product = Product::query()->where('name', 'Producto tras borrado')->firstOrFail();

    expect($product->code)->not->toBe($takenCode);
});

test('a stale auto-generated code that just got taken is retried instead of failing the request', function () {
    $admin = codeGenAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $controller = app(App\Http\Controllers\InventarioController::class);
    $method = new ReflectionMethod($controller, 'createProductRetryingAutoCode');
    $method->setAccessible(true);

    $staleCode = app(App\Services\InventoryService::class)->nextProductCode();

    Product::create([
        'category_id' => $category->id,
        'name' => 'Ocupante concurrente de prueba',
        'code' => $staleCode,
        'purchase_price' => 1,
        'sale_price' => 2,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
    ]);

    $product = $method->invoke($controller, [
        'category_id' => $category->id,
        'name' => 'Producto con reintento',
        'code' => $staleCode,
        'purchase_price' => 5,
        'sale_price' => 10,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ], true);

    expect($product->code)->not->toBe($staleCode)
        ->and(Product::where('name', 'Producto con reintento')->exists())->toBeTrue();
});
