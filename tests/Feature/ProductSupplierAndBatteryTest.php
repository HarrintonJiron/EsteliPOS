<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function productSupplierAdmin(): User
{
    test()->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

test('the pro form links a supplier and stores battery percentage', function () {
    $admin = productSupplierAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $supplier = Supplier::create(['name' => 'Distribuidora Móvil', 'phone' => '88880000', 'status' => 'active']);

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'iPhone 13 seminuevo',
        'condition' => 'used',
        'battery_percentage' => 87,
        'supplier_id' => $supplier->id,
        'supplier_code' => 'DM-IPH13-001',
        'purchase_price' => 300,
        'sale_price' => 420,
        'stock' => 1,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $product = Product::query()->where('name', 'iPhone 13 seminuevo')->firstOrFail();

    expect($product->battery_percentage)->toBe(87)
        ->and($product->suppliers)->toHaveCount(1);

    $link = $product->suppliers->first();
    expect($link->id)->toBe($supplier->id)
        ->and((float) $link->pivot->purchase_price)->toBe(300.0)
        ->and($link->pivot->supplier_code)->toBe('DM-IPH13-001')
        ->and((bool) $link->pivot->preferred)->toBeTrue();
});

test('registro rapido links a supplier without requiring battery or condition fields', function () {
    $admin = productSupplierAdmin();
    Category::firstOrCreate(['name' => 'Celulares']);
    $supplier = Supplier::create(['name' => 'Mayoreo Central', 'phone' => '88881111', 'status' => 'active']);

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'name' => 'Cargador rápido 20W',
        'sale_price' => 12,
        'supplier_id' => $supplier->id,
    ])->assertSessionHasNoErrors();

    $product = Product::query()->where('name', 'Cargador rápido 20W')->firstOrFail();

    expect($product->suppliers)->toHaveCount(1)
        ->and($product->suppliers->first()->id)->toBe($supplier->id);
});

test('editing a product can change its linked supplier and battery percentage', function () {
    $admin = productSupplierAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $supplierA = Supplier::create(['name' => 'Proveedor A', 'phone' => '88882222', 'status' => 'active']);
    $supplierB = Supplier::create(['name' => 'Proveedor B', 'phone' => '88883333', 'status' => 'active']);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Samsung A54',
        'code' => 'SAMS-A54-EDIT',
        'condition' => 'used',
        'battery_percentage' => 70,
        'purchase_price' => 200,
        'sale_price' => 280,
        'stock' => 1,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);
    $product->suppliers()->attach($supplierA->id, ['purchase_price' => 200, 'preferred' => true]);

    $this->actingAs($admin)->put(route('inventario.update', $product->id), [
        'category_id' => $category->id,
        'name' => 'Samsung A54',
        'code' => 'SAMS-A54-EDIT',
        'condition' => 'used',
        'battery_percentage' => 91,
        'supplier_id' => $supplierB->id,
        'supplier_code' => 'PB-A54',
        'purchase_price' => 210,
        'sale_price' => 285,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $product->refresh();

    expect($product->battery_percentage)->toBe(91)
        ->and($product->suppliers()->where('supplier_id', $supplierB->id)->exists())->toBeTrue();
});

test('battery percentage is rejected outside 0-100', function () {
    $admin = productSupplierAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Producto con bateria invalida',
        'battery_percentage' => 150,
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 1,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertSessionHasErrors('battery_percentage');
});

test('changing the supplier leaves only the new one flagged as preferred', function () {
    $admin = productSupplierAdmin();
    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $supplierA = Supplier::create(['name' => 'Proveedor Viejo', 'phone' => '88884444', 'status' => 'active']);
    $supplierB = Supplier::create(['name' => 'Proveedor Nuevo', 'phone' => '88885555', 'status' => 'active']);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Xiaomi Redmi',
        'code' => 'XIA-PREF',
        'purchase_price' => 150,
        'sale_price' => 220,
        'stock' => 1,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);
    $product->suppliers()->attach($supplierA->id, ['purchase_price' => 150, 'preferred' => true]);

    $this->actingAs($admin)->put(route('inventario.update', $product->id), [
        'category_id' => $category->id,
        'name' => 'Xiaomi Redmi',
        'code' => 'XIA-PREF',
        'supplier_id' => $supplierB->id,
        'purchase_price' => 160,
        'sale_price' => 230,
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $product->refresh();
    $preferred = $product->suppliers()->wherePivot('preferred', true)->get();

    expect($preferred)->toHaveCount(1)
        ->and($preferred->first()->id)->toBe($supplierB->id);

    // El proveedor anterior sigue asociado, solo deja de ser el preferido.
    expect($product->suppliers()->where('supplier_id', $supplierA->id)->exists())->toBeTrue();

    // Y el formulario de edición ya muestra el nuevo.
    $this->actingAs($admin)->get(route('inventario.edit', $product->id))
        ->assertOk()
        ->assertViewHas('currentSupplier', fn ($supplier) => $supplier?->id === $supplierB->id);
});
