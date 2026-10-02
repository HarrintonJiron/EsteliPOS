<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Role;
use App\Models\Sale;
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

test('proforma ignores a manipulated browser price', function () {
    $admin = proformaTaxAdmin();
    Tax::query()->update(['is_default' => false, 'is_active' => false]);
    Tax::create(['code' => 'EXENTO-PRICE', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    $product = proformaTaxProduct();

    $this->actingAs($admin)->post(route('proformas.store'), [
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 0.01,
            'discount' => 0,
        ]]),
    ])->assertRedirect();

    $proforma = Proforma::query()->with('details')->latest('id')->firstOrFail();
    expect((float) $proforma->subtotal)->toBe(200.0)
        ->and((float) $proforma->details->first()->price)->toBe(100.0);
});

test('an existing proforma can change its client quantities products and description', function () {
    $admin = proformaTaxAdmin();
    Tax::query()->update(['is_default' => false, 'is_active' => false]);
    Tax::create(['code' => 'EXENTO-EDIT', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    $firstProduct = proformaTaxProduct();
    $secondProduct = proformaTaxProduct();
    $client = Client::create([
        'code' => 'CLI-EDIT',
        'name' => 'Cliente edición',
        'client_type' => 'natural',
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('proformas.store'), [
        'items' => json_encode([[
            'product_id' => $firstProduct->id,
            'quantity' => 1,
            'discount' => 0,
        ]]),
    ])->assertRedirect();

    $proforma = Proforma::query()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('proformas.index'))
        ->assertOk()
        ->assertSee(route('proformas.edit', $proforma), false)
        ->assertSee('Editar');

    $this->actingAs($admin)
        ->get(route('proformas.edit', $proforma))
        ->assertOk()
        ->assertSee('Editar '.$proforma->proforma_number)
        ->assertSee($firstProduct->name);

    $this->actingAs($admin)->put(route('proformas.update', $proforma), [
        'client_id' => $client->id,
        'expiry_days' => 30,
        'notes' => 'Entregar en bodega norte.',
        'items' => json_encode([
            ['product_id' => $firstProduct->id, 'quantity' => 3, 'discount' => 0],
            ['product_id' => $secondProduct->id, 'quantity' => 2, 'discount' => 10],
        ]),
    ])->assertRedirect(route('proformas.show', $proforma));

    $proforma->refresh()->load('details');
    expect($proforma->client_id)->toBe($client->id)
        ->and($proforma->notes)->toBe('Entregar en bodega norte.')
        ->and($proforma->details)->toHaveCount(2)
        ->and((float) $proforma->details->firstWhere('product_id', $firstProduct->id)->quantity)->toBe(3.0)
        ->and((float) $proforma->subtotal)->toBe(480.0)
        ->and((float) $proforma->total)->toBe(480.0);

    $this->actingAs($admin)
        ->get(route('proformas.ticket', $proforma))
        ->assertOk()
        ->assertSee('80mm', false)
        ->assertSee('Entregar en bodega norte.');
});

test('a proforma converted into a sale cannot be edited', function () {
    $admin = proformaTaxAdmin();
    $client = Client::create([
        'code' => 'CLI-SALE',
        'name' => 'Cliente venta',
        'client_type' => 'natural',
        'status' => 'active',
    ]);
    $sale = Sale::create([
        'invoice_number' => 'FAC-LOCKED',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'date' => now(),
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);
    $proforma = Proforma::create([
        'proforma_number' => 'PRO-LOCKED',
        'user_id' => $admin->id,
        'sale_id' => $sale->id,
        'client_name' => 'Cliente General',
        'date' => now(),
        'expiry_date' => now()->addDays(15),
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
        'tax_rate' => 0,
        'status' => 'accepted',
    ]);

    $this->actingAs($admin)
        ->get(route('proformas.edit', $proforma))
        ->assertRedirect(route('proformas.show', $proforma))
        ->assertSessionHas('error');
});
