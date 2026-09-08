<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function receiptAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('pos receipt shows item and invoice discounts for the customer', function () {
    $admin = receiptAdmin();
    $category = Category::firstOrCreate(['name' => 'Descuentos']);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Pintura blanca',
        'code' => 'PINT-DTO-01',
        'purchase_price' => 100,
        'sale_price' => 200,
        'stock' => 20,
        'unit' => 'galón',
        'status' => 'active',
    ]);

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($admin)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 2,
            'discount' => 10,
        ]]),
        'amount_received' => 1000,
        'order_discount_pct' => 5,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->with('details')->latest('id')->firstOrFail();

    expect((float) $sale->discount_percentage)->toBe(5.0)
        ->and((float) $sale->discount_amount)->toBeGreaterThan(0)
        ->and((float) $sale->details->first()->discount_percentage)->toBe(10.0);

    $this->actingAs($admin)
        ->get(route('facturacion.receipt', ['saleId' => $sale->id, 'change' => 0]))
        ->assertOk()
        ->assertSee('DESCUENTO')
        ->assertSee('Dto. 10.00%')
        ->assertSee('Ahorraste')
        ->assertSee('5.00% factura');
});
