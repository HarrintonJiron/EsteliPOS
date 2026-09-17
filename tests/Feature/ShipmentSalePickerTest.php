<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Shipment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pickerAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

function pickerSale(User $admin, string $invoice, array $attributes = [], array $productNames = []): Sale
{
    $sale = Sale::create($attributes + [
        'invoice_number' => $invoice,
        'client_id' => Client::firstOrCreate(['name' => 'Cliente de mostrador'], ['status' => 'active'])->id,
        'user_id' => $admin->id,
        'billing_name' => 'Cliente Genérico',
        'date' => now()->toDateString(),
        'payment_type' => 'cash',
        'status' => 'completed',
        'tax_included' => false,
        'tax_rate' => 0.15,
        'subtotal' => 100,
        'tax_total' => 15,
        'total' => 115,
    ]);

    if ($productNames) {
        $category = Category::firstOrCreate(['name' => 'Celulares']);
        $unit = Unit::firstOrCreate(['abbreviation' => 'und'], ['name' => 'Unidad', 'is_active' => true]);

        foreach ($productNames as $index => $name) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $name,
                'code' => 'PK-'.$invoice.'-'.$index,
                'purchase_price' => 50,
                'sale_price' => 100,
                'stock' => 5,
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
        }
    }

    return $sale;
}

test('the sale picker describes each sale with client, products, date, payment and total instead of only an invoice number', function () {
    $admin = pickerAdmin();
    $client = Client::create(['name' => 'María López', 'phone' => '88887777', 'status' => 'active']);
    pickerSale($admin, 'FAC-000045', ['client_id' => $client->id, 'billing_name' => 'María López', 'total' => 1067.20, 'payment_type' => 'credit'], ['iPhone 14 128GB', 'Samsung Galaxy A55 5G', 'Funda transparente']);

    $this->actingAs($admin)->get(route('envios.create'))
        ->assertOk()
        ->assertSeeText('María López')
        ->assertSeeText('iPhone 14 128GB, Samsung Galaxy A55 5G')
        ->assertSeeText('+1 más')
        ->assertSeeText('Crédito')
        ->assertSeeText('1,067.20')
        ->assertSeeText('Factura FAC-000045')
        ->assertSeeText('Sin factura relacionada')
        ->assertSee('id="sale-picker-search"', false);
});

test('cancelled sales are not offered for a shipment', function () {
    $admin = pickerAdmin();
    pickerSale($admin, 'FAC-VIVA', ['billing_name' => 'Cliente Vigente']);
    pickerSale($admin, 'FAC-ANULADA', ['billing_name' => 'Cliente Anulado', 'status' => 'cancelled']);

    $this->actingAs($admin)->get(route('envios.create'))
        ->assertOk()
        ->assertSeeText('Cliente Vigente')
        ->assertDontSeeText('Cliente Anulado')
        ->assertDontSeeText('FAC-ANULADA');
});

test('a sale that already has an active shipment is flagged, but a cancelled one is not', function () {
    $admin = pickerAdmin();
    $sale = pickerSale($admin, 'FAC-ENVIADA', ['billing_name' => 'Cliente Con Envío']);
    $other = pickerSale($admin, 'FAC-LIBRE', ['billing_name' => 'Cliente Libre']);

    Shipment::create(['number' => 'ENV-0001', 'sale_id' => $sale->id, 'user_id' => $admin->id, 'recipient_name' => 'X', 'department' => 'Managua', 'address' => 'Y', 'status' => 'shipped']);
    Shipment::create(['number' => 'ENV-0002', 'sale_id' => $other->id, 'user_id' => $admin->id, 'recipient_name' => 'X', 'department' => 'Managua', 'address' => 'Y', 'status' => 'cancelled']);

    $this->actingAs($admin)->get(route('envios.create'))
        ->assertOk()
        ->assertSeeText('Ya tiene envío ENV-0001')
        ->assertDontSeeText('Ya tiene envío ENV-0002');
});

test('creating a shipment from a sale preselects it and prefills the recipient', function () {
    $admin = pickerAdmin();
    $client = Client::create(['name' => 'Pedro Ruiz', 'phone' => '85551234', 'department' => 'León', 'municipality' => 'Nagarote', 'address' => 'Barrio Sutiaba', 'status' => 'active']);
    $sale = pickerSale($admin, 'FAC-000777', ['client_id' => $client->id, 'billing_name' => 'Pedro Ruiz'], ['iPhone 13 128GB']);

    $this->actingAs($admin)->get(route('envios.create', ['sale_id' => $sale->id]))
        ->assertOk()
        ->assertSee('value="'.$sale->id.'"', false)
        ->assertSee('checked', false)
        ->assertSee('value="Pedro Ruiz"', false)
        ->assertSee('value="85551234"', false);
});

test('the chosen sale is still offered when it is older than the latest hundred', function () {
    $admin = pickerAdmin();
    $old = pickerSale($admin, 'FAC-ANTIGUA', ['billing_name' => 'Cliente Antiguo', 'date' => now()->subYear()->toDateString()]);

    foreach (range(1, 101) as $i) {
        pickerSale($admin, 'FAC-N'.$i, ['billing_name' => 'Cliente Reciente']);
    }

    $this->actingAs($admin)->get(route('envios.create'))->assertOk()->assertDontSeeText('Cliente Antiguo');

    $shipment = Shipment::create(['number' => 'ENV-OLD', 'sale_id' => $old->id, 'user_id' => $admin->id, 'recipient_name' => 'Z', 'department' => 'Managua', 'address' => 'Q', 'status' => 'pending']);

    $this->actingAs($admin)->get(route('envios.edit', $shipment))
        ->assertOk()
        ->assertSeeText('Cliente Antiguo')
        ->assertSeeText('Se muestran las últimas 100 ventas');
});

test('a shipment can still be saved with and without a related sale', function () {
    $admin = pickerAdmin();
    $sale = pickerSale($admin, 'FAC-GUARDAR', ['billing_name' => 'Cliente Guardar']);

    $payload = ['recipient_name' => 'Destinatario', 'department' => 'Estelí', 'address' => 'Calle 1', 'status' => 'pending'];

    $this->actingAs($admin)->post(route('envios.store'), $payload + ['sale_id' => $sale->id])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('envios.store'), $payload + ['sale_id' => ''])->assertSessionHasNoErrors();

    expect(Shipment::query()->where('sale_id', $sale->id)->count())->toBe(1)
        ->and(Shipment::query()->whereNull('sale_id')->count())->toBe(1);
});

test('placeholder phone numbers such as N/A are not copied into the shipment', function () {
    $admin = pickerAdmin();
    $client = Client::create(['name' => 'Cliente Teléfono', 'phone' => '85550000', 'status' => 'active']);
    pickerSale($admin, 'FAC-NA', ['client_id' => $client->id, 'billing_name' => 'Cliente Teléfono', 'billing_phone' => 'N/A']);

    $this->actingAs($admin)->get(route('envios.create'))
        ->assertOk()
        ->assertSee('data-phone="85550000"', false)
        ->assertDontSee('data-phone="N/A"', false);
});

test('the sale picker is a collapsible dropdown that starts closed', function () {
    $admin = pickerAdmin();
    pickerSale($admin, 'FAC-DESPLEGABLE', ['billing_name' => 'Cliente Desplegable']);

    $this->actingAs($admin)->get(route('envios.create'))
        ->assertOk()
        ->assertSee('id="sale-picker-toggle"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('id="sale-picker-panel" class="mt-2 hidden', false)
        ->assertSee('id="sale-picker-summary"', false);
});

test('a shipment started from a sale with a placeholder phone falls back to the client phone', function () {
    $admin = pickerAdmin();
    $client = Client::create(['name' => 'Cliente Con Teléfono', 'phone' => '85557777', 'status' => 'active']);
    $sale = pickerSale($admin, 'FAC-PH', ['client_id' => $client->id, 'billing_phone' => 'N/A']);

    $this->actingAs($admin)->get(route('envios.create', ['sale_id' => $sale->id]))
        ->assertOk()
        ->assertSee('id="recipient-phone"', false)
        ->assertSee('value="85557777"', false)
        ->assertDontSee('value="N/A"', false);
});

test('a voided sale stored as canceled with one l is not offered either', function () {
    $admin = pickerAdmin();
    pickerSale($admin, 'FAC-BUENA', ['billing_name' => 'Cliente Bueno']);
    pickerSale($admin, 'FAC-VOID', ['billing_name' => 'Cliente Anulado Void', 'status' => 'canceled']);

    $this->actingAs($admin)->get(route('envios.create'))
        ->assertOk()
        ->assertSeeText('Cliente Bueno')
        ->assertDontSeeText('Cliente Anulado Void')
        ->assertDontSeeText('FAC-VOID');
});
