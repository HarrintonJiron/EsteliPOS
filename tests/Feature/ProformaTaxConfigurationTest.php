<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Role;
use App\Models\Tax;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function proformaTaxAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);

    return $user;
}

function proformaTaxProduct(): Product
{
    $category = Category::firstOrCreate(['name' => 'Pruebas proforma']);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Producto proforma',
        'code' => 'PRO-TAX-'.str()->random(6),
        'purchase_price' => 50,
        'sale_price' => 100,
        'stock' => 10,
        'unit' => 'unidad',
        'status' => 'active',
    ]);
}

test('proforma pos uses zero tax when default tax is exempt', function () {
    $admin = proformaTaxAdmin();
    Tax::query()->update(['is_default' => false, 'is_active' => false]);
    Tax::create(['code' => 'EXENTO-PRO', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    proformaTaxProduct();

    $response = $this->actingAs($admin)->get(route('proformas.pos'));

    $response->assertOk()
        ->assertSee('data-default-tax-rate="0"', false);

    expect((float) collect($response->viewData('products'))->first()['effective_tax_rate'])->toBe(0.0)
        ->and((float) $response->viewData('defaultTaxRate'))->toBe(0.0);
});

test('proforma store does not apply fifteen percent when tax is disabled', function () {
    $admin = proformaTaxAdmin();
    Tax::query()->update(['is_default' => false, 'is_active' => false]);
    Tax::create(['code' => 'EXENTO-STORE', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    $product = proformaTaxProduct();

    $this->actingAs($admin)->post(route('proformas.store'), [
        'items' => json_encode([[
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 1,
            'price' => 100,
            'discount' => 0,
            'tax_rate' => 0.15,
        ]]),
        'expiry_days' => 15,
    ])->assertRedirect();

    $proforma = Proforma::latest('id')->firstOrFail();

    expect((float) $proforma->subtotal)->toBe(100.0)
        ->and((float) $proforma->tax_rate)->toBe(0.0)
        ->and((float) $proforma->tax_total)->toBe(0.0)
        ->and((float) $proforma->total)->toBe(100.0);
});
