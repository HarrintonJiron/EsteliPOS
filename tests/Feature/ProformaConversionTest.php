<?php

use App\Http\Controllers\ProformaController;
use App\Models\Client;
use App\Models\Proforma;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('converting a proforma without a client creates a generic client sale', function () {
    $this->seed(InventoryCatalogSeeder::class);
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
    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $response = app(ProformaController::class)->convertToSale(new Request([
        'payment_type' => 'transfer',
        'warehouse_id' => Warehouse::query()->where('is_default', true)->value('id'),
    ]), $proforma->id);

    $genericClient = Client::where('code', 'GEN')->first();

    expect($genericClient)->not->toBeNull();
    expect($response->getTargetUrl())->toContain('/facturacion/');
    $this->assertDatabaseHas('sales', [
        'client_id' => $genericClient->id,
        'invoice_number' => 'FAC-000001',
    ]);
});
