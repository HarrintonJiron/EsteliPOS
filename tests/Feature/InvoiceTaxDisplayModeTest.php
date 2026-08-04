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
