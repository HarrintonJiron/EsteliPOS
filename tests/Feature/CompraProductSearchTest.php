<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function comprasAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('purchase product search returns any active inventory product', function () {
    $admin = comprasAdmin();
    $category = Category::create(['name' => 'General']);

    $linked = Product::create([
        'category_id' => $category->id,
        'name' => 'Cemento Holcim',
        'code' => 'CEM-001',
        'purchase_price' => 250,
        'sale_price' => 320,
        'stock' => 10,
        'unit' => 'unidad',
        'status' => 'active',
    ]);

    $other = Product::create([
        'category_id' => $category->id,
        'name' => 'Varilla 3/8',
        'code' => 'VAR-038',
        'purchase_price' => 45,
        'sale_price' => 60,
        'stock' => 100,
        'unit' => 'unidad',
        'status' => 'active',
    ]);

    $supplier = Supplier::create(['name' => 'Distribuidora Norte', 'status' => 'active']);
    $linked->suppliers()->attach($supplier->id, ['purchase_price' => 230]);

    $response = $this->actingAs($admin)->getJson(route('compras.products.search', [
        'search' => 'Varilla',
        'supplier_id' => $supplier->id,
    ]));

    $response->assertSuccessful()
        ->assertJsonFragment(['code' => 'VAR-038', 'price' => 45, 'has_supplier_price' => false]);

    $response = $this->actingAs($admin)->getJson(route('compras.products.search', [
        'search' => 'Cemento',
        'supplier_id' => $supplier->id,
    ]));

    $response->assertSuccessful()
        ->assertJsonFragment(['code' => 'CEM-001', 'price' => 230, 'has_supplier_price' => true]);
});
