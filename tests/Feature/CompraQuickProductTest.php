<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('purchase form can create a product quickly and link supplier cost', function () {
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $category = Category::create(['name' => 'General']);
    $supplier = Supplier::create(['name' => 'Distribuidora Norte', 'status' => 'active']);

    $response = $this->actingAs($admin)->postJson(route('compras.products.quick-store'), [
        'name' => 'Tornillo 1/2',
        'purchase_price' => 12.50,
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('product.name', 'Tornillo 1/2')
        ->assertJsonPath('product.price', 12.5)
        ->assertJsonPath('product.has_supplier_price', true);

    $product = Product::query()->where('name', 'Tornillo 1/2')->firstOrFail();

    expect((float) $product->purchase_price)->toBe(12.5)
        ->and((float) $product->sale_price)->toBe(14.71)
        ->and($product->suppliers()->where('supplier_id', $supplier->id)->exists())->toBeTrue();

    $pivotPrice = (float) $product->suppliers()->where('supplier_id', $supplier->id)->first()->pivot->purchase_price;
    expect($pivotPrice)->toBe(12.5);
});

test('next product code endpoint returns a unique code', function () {
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)->getJson(route('compras.products.next-code'))
        ->assertSuccessful()
        ->assertJsonStructure(['code']);
});

test('quick product creation requires a category when none exists', function () {
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)->postJson(route('compras.products.quick-store'), [
        'name' => 'Producto sin categoría',
        'purchase_price' => 5,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});
