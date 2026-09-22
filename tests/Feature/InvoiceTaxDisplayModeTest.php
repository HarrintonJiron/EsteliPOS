<?php

use App\Models\Role;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Client;
use App\Models\User;
use App\Services\InvoiceTaxDisplayService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminForTaxDisplayTests(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);

    return $user;
}

function createSampleSale(User $admin): Sale
{
    $client = Client::create([
        'name' => 'Cliente Prueba',
        'legal_name' => 'Cliente Prueba',
        'phone' => '88880000',
        'email' => 'cliente-prueba@example.com',
        'address' => 'Esteli',
    ]);

    return Sale::create([
        'invoice_number' => 'FAC-TAX-001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => 'Cliente Prueba',
        'billing_document_type' => 'cedula',
        'billing_ruc' => '001-010101-0001A',
        'date' => now()->toDateString(),
        'payment_type' => 'cash',
        'status' => 'completed',
        'tax_included' => false,
        'tax_rate' => 0.15,
        'subtotal' => 100,
        'tax_total' => 15,
        'total' => 115,
    ]);
}

test('it saves global invoice tax display mode from settings taxes screen', function () {
    $admin = adminForTaxDisplayTests();

    $this->actingAs($admin)
        ->post(route('settings.taxes.display-mode.update'), [
            'invoice_tax_display_mode' => InvoiceTaxDisplayService::MODE_HIDE,
        ])
        ->assertRedirect(route('settings.taxes.index'));

    expect(Setting::get(InvoiceTaxDisplayService::SETTING_KEY))->toBe(InvoiceTaxDisplayService::MODE_HIDE);
});

test('invoice print hides tax breakdown when mode is hide', function () {
    $admin = adminForTaxDisplayTests();
    $sale = createSampleSale($admin);

    Setting::set(InvoiceTaxDisplayService::SETTING_KEY, InvoiceTaxDisplayService::MODE_HIDE, 'string', 'general');

    $this->actingAs($admin)
        ->get(route('facturacion.print', ['sale_id' => $sale->id]))
        ->assertOk()
        ->assertDontSee('Subtotal:', false)
        ->assertDontSee('IVA (', false)
        ->assertSee('Total:', false);
});

test('invoice print shows exempt label when mode is exempt', function () {
    $admin = adminForTaxDisplayTests();
    $sale = createSampleSale($admin);

    Setting::set(InvoiceTaxDisplayService::SETTING_KEY, InvoiceTaxDisplayService::MODE_EXEMPT, 'string', 'general');

    $this->actingAs($admin)
        ->get(route('facturacion.print', ['sale_id' => $sale->id]))
        ->assertOk()
        ->assertSee('Exento de IVA:', false)
        ->assertSee('C$ 0.00', false);
});

test('product device data is rendered in every invoice print format', function () {
    $admin = adminForTaxDisplayTests();
    $sale = createSampleSale($admin);
    $category = Category::create(['name' => 'Celulares']);
    $unit = Unit::create(['name' => 'Unidad', 'abbreviation' => 'und', 'is_active' => true]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'iPhone 13 Pro',
        'code' => 'IPH-SPECS',
        'brand' => 'Apple',
        'model' => 'A2638',
        'color' => 'Azul sierra',
        'imei' => '356789012345678',
        'battery_percentage' => 87,
        'condition' => 'used',
        'description' => "Incluye cargador\ny funda original.",
        'purchase_price' => 80,
        'sale_price' => 100,
        'stock' => 1,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    SaleDetail::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 1,
        'unit_factor' => 1,
        'base_quantity' => 1,
        'price' => 100,
        'subtotal' => 100,
        'tax_rate' => 0,
        'tax_amount' => 0,
    ]);

    foreach ([
        route('facturacion.print', ['sale_id' => $sale->id]),
        route('facturacion.pdf', ['sale_id' => $sale->id]),
        route('facturacion.receipt', ['saleId' => $sale->id]),
    ] as $url) {
        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertSeeText('iPhone 13 Pro')
            ->assertSeeText('Marca: Apple')
            ->assertSeeText('Modelo: A2638')
            ->assertSeeText('Azul sierra')
            ->assertSeeText('356789012345678')
            ->assertSeeText('87%')
            ->assertSeeText('Incluye cargador y funda original.');
    }
});

test('product without device data adds no extra lines to the invoice', function () {
    $product = new Product(['name' => 'Tornillo', 'condition' => null, 'battery_percentage' => null]);

    expect($product->invoiceSpecs())->toBe([]);
});
