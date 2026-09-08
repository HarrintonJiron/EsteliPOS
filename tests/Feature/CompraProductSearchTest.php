<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
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

test('purchase product search only returns active products linked to the selected supplier', function () {
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
        ->assertExactJson([]);

    $response = $this->actingAs($admin)->getJson(route('compras.products.search', [
        'search' => 'Cemento',
        'supplier_id' => $supplier->id,
    ]));

    $response->assertSuccessful()
        ->assertJsonFragment(['code' => 'CEM-001', 'price' => 230, 'has_supplier_price' => true]);

    $this->actingAs($admin)->getJson(route('compras.products.search', [
        'supplier_id' => $supplier->id,
    ]))->assertSuccessful()
        ->assertJsonFragment(['code' => 'CEM-001']);
});

test('purchase product search recognizes products bought historically from the supplier', function () {
    $admin = comprasAdmin();
    $category = Category::create(['name' => 'Histórico']);
    $supplier = Supplier::create(['name' => 'Proveedor histórico', 'status' => 'active']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto histórico',
        'code' => 'HIST-001',
        'purchase_price' => 80,
        'sale_price' => 100,
        'stock' => 3,
        'unit' => 'unidad',
        'status' => 'active',
    ]);
    $purchase = Purchase::create([
        'supplier_id' => $supplier->id,
        'user_id' => $admin->id,
        'date' => now()->toDateString(),
        'subtotal' => 80,
        'tax_total' => 0,
        'total' => 80,
        'status' => 'completed',
        'payment_type' => 'cash',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'foreign_subtotal' => 80,
        'foreign_tax_total' => 0,
        'foreign_total' => 80,
    ]);
    PurchaseDetail::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'base_quantity' => 1,
        'price' => 80,
        'subtotal' => 80,
        'tax_rate' => 0,
        'tax_amount' => 0,
    ]);

    $this->actingAs($admin)->getJson(route('compras.products.search', [
        'supplier_id' => $supplier->id,
    ]))->assertSuccessful()
        ->assertJsonFragment([
            'code' => 'HIST-001',
            'price' => 80,
        ]);
});

test('purchase product search requires a supplier', function () {
    $admin = comprasAdmin();

    $this->actingAs($admin)->getJson(route('compras.products.search', [
        'search' => 'Cemento',
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['supplier_id']);
});
