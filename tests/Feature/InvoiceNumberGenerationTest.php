<?php

use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\ProformaController;
use App\Models\Client;
use App\Models\Proforma;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('facturacion controller generates the next configured invoice sequence without regex sql', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    $client = Client::create([
        'name' => 'Test Client',
        'phone' => '0000',
    ]);

    Sale::create([
        'invoice_number' => '000001',
        'client_id' => $client->id,
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'total' => 10,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);

    Sale::create([
        'invoice_number' => 'ABC-123',
        'client_id' => $client->id,
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'total' => 10,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);

    $controller = app(FacturacionController::class);
    $method = new ReflectionMethod($controller, 'nextInvoiceNumber');
    $method->setAccessible(true);

    expect($method->invoke($controller))->toBe('FAC-000001');
});

test('proforma controller generates the next proforma number without regex sql', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'proforma@example.com',
        'password' => bcrypt('password'),
    ]);

    Proforma::create([
        'proforma_number' => 'PRO-000001',
        'client_id' => null,
        'user_id' => $user->id,
        'client_name' => 'Cliente',
        'date' => now()->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'tax_rate' => 0.15,
        'tax_included' => false,
        'status' => 'draft',
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
    ]);

    Proforma::create([
        'proforma_number' => 'INV-000002',
        'client_id' => null,
        'user_id' => $user->id,
        'client_name' => 'Cliente',
        'date' => now()->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'tax_rate' => 0.15,
        'tax_included' => false,
        'status' => 'draft',
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
    ]);

    $controller = app(ProformaController::class);
    $method = new ReflectionMethod($controller, 'nextProformaNumber');
    $method->setAccessible(true);

    expect($method->invoke($controller))->toBe('PRO-000002');
});
