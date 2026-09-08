<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inventory report formats a product expiry date without a server error', function () {
    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);
    $category = Category::create(['name' => 'General']);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto con vencimiento',
        'code' => 'VENCE-001',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 5,
        'unit' => 'unidad',
        'expiry_date' => '2027-05-21',
        'status' => 'active',
    ]);

    expect($product->fresh()->toArray()['expiry_date'])->toBe('2027-05-21');

    $this->actingAs($user)
        ->get(route('reportes.index', ['report_type' => 'inventory']))
        ->assertOk()
        ->assertSee('21/05/2027');
});
