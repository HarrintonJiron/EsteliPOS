<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CreditService;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financialIntegrityAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

test('pending and canceled purchases do not alter inventory or accounting', function () {
    $admin = financialIntegrityAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplier = Supplier::query()->create(['name' => 'Proveedor integridad', 'status' => 'active']);
    $category = Category::firstOrCreate(['name' => 'Integridad']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto compra pendiente',
        'code' => 'INT-COMPRA-1',
        'purchase_price' => 25,
        'sale_price' => 40,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $payload = [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 3,
            'price' => 25,
        ]],
    ];

    $this->actingAs($admin)->post(route('compras.store'), $payload)
        ->assertRedirect(route('compras.index'));

    $purchase = Purchase::query()->firstOrFail();
    expect((float) $product->fresh()->stock)->toBe(0.0)
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->exists())->toBeFalse();

    $payload['status'] = 'completed';
    $this->actingAs($admin)->put(route('compras.update', $purchase), $payload)
        ->assertRedirect(route('compras.index'));
    expect((float) $product->fresh()->stock)->toBe(3.0);

    $payload['status'] = 'canceled';
    $this->actingAs($admin)->put(route('compras.update', $purchase), $payload)
        ->assertRedirect(route('compras.index'));

    expect((float) $product->fresh()->stock)->toBe(0.0)
        ->and(JournalEntry::query()
            ->where('source_type', Purchase::class)
            ->where('source_id', $purchase->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->exists())->toBeFalse();
});

test('repair order stores the discounted total and correct balance', function () {
    $admin = financialIntegrityAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        'client_name' => 'Cliente descuento',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone',
        'problem_description' => 'Pantalla dañada',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'labor_cost' => 100,
        'discount_percentage' => 10,
        'discount_amount' => 5,
        'advance_payment' => 20,
        'payment_type' => 'cash',
    ])->assertRedirect();

    $order = RepairOrder::query()->latest('id')->firstOrFail();

    expect((float) $order->total)->toBe(85.0)
        ->and($order->balance())->toBe(65.0)
        ->and($order->payment_status)->toBe('partial');
});

test('credit payments cannot exceed debt and aging uses the outstanding balance', function () {
    $admin = financialIntegrityAdmin();
    $client = Client::query()->create([
        'name' => 'Cliente crédito integridad',
        'phone' => '88880000',
        'credit_enabled' => true,
        'credit_limit' => 1000,
    ]);
    Sale::query()->create([
        'invoice_number' => 'CRED-INT-001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'date' => now()->subDays(60),
        'due_date' => now()->subDays(40),
        'payment_type' => 'credit',
        'status' => 'pending',
        'tax_included' => false,
        'tax_rate' => 0,
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
    ]);

    CreditPayment::query()->create([
        'client_id' => $client->id,
        'amount' => 30,
        'payment_type' => 'cash',
        'payment_date' => now(),
        'user_id' => $admin->id,
    ]);

    $credit = app(CreditService::class);
    expect($credit->pendingDebt($client))->toBe(70.0)
        ->and($credit->agingReport()['days_31_60'])->toBe(70.0)
        ->and($credit->portfolioSummary()['overdue_total'])->toBe(70.0);

    $this->actingAs($admin)
        ->from(route('creditos.create', $client))
        ->post(route('creditos.store'), [
            'client_id' => $client->id,
            'amount' => 70.01,
            'payment_type' => 'cash',
        ])
        ->assertRedirect(route('creditos.create', $client))
        ->assertSessionHas('error');

    expect((float) CreditPayment::query()->sum('amount'))->toBe(30.0);
});
