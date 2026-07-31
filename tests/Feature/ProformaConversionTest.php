<?php

use App\Http\Controllers\ProformaController;
use App\Models\Client;
use App\Models\Proforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('converting a proforma without a client creates a generic client sale', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'convert@example.com',
        'password' => bcrypt('password'),
    ]);

    $proforma = Proforma::create([
        'proforma_number' => 'PRO-000010',
        'client_id' => null,
        'user_id' => $user->id,
        'client_name' => 'Cliente General',
        'date' => now()->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'tax_rate' => 0.15,
        'tax_included' => false,
        'status' => 'draft',
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
    ]);

    $this->actingAs($user);

    $controller = new ProformaController();
    $response = $controller->convertToSale(new Request(['payment_type' => 'cash']), $proforma->id);

    $genericClient = Client::where('code', 'GEN')->first();

    expect($genericClient)->not->toBeNull();
    expect($response->getTargetUrl())->toContain('/facturacion/');
    $this->assertDatabaseHas('sales', [
        'client_id' => $genericClient->id,
        'invoice_number' => '000001',
    ]);
});
