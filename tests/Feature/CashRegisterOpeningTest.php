<?php

use App\Models\Arqueo;
use App\Models\CajaSession;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cashRegisterAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $adminRole = Role::query()->where('slug', 'admin')->first();
    if ($adminRole) {
        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }

    return $user;
}

test('cash register opens with the entered opening amount', function () {
    $admin = cashRegisterAdmin();

    $this->actingAs($admin)
        ->post(route('arqueo.open'), ['opening_amount' => '1,250.50'])
        ->assertRedirect(route('facturacion.pos'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $session = CajaSession::query()->firstOrFail();

    expect((float) $session->opening_amount)->toBe(1250.50)
        ->and($session->opened_by)->toBe($admin->id)
        ->and($session->status)->toBe('open');
});

test('cash register opening amount is required and cannot be negative', function (mixed $openingAmount) {
    $admin = cashRegisterAdmin();

    $this->actingAs($admin)
        ->from(route('arqueo.index'))
        ->post(route('arqueo.open'), ['opening_amount' => $openingAmount])
        ->assertRedirect(route('arqueo.index'))
        ->assertSessionHasErrors('opening_amount');

    expect(CajaSession::query()->exists())->toBeFalse();
})->with([
    'missing amount' => null,
    'negative amount' => -1,
    'invalid amount' => 'efectivo',
]);

test('open cash screen shows compact closing summary when session is active', function () {
    $admin = cashRegisterAdmin();
    CajaSession::query()->create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'opening_amount' => 750,
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->get(route('arqueo.index', ['cerrar' => 1]))
        ->assertOk()
        ->assertSee('Caja abierta')
        ->assertSee('C$ 750.00')
        ->assertSee('Cerrar caja')
        ->assertSee('Esperado')
        ->assertSee('Conteo y cierre')
        ->assertSee('qty-input', false)
        ->assertSee('denom-row', false);
});

test('opening amount is included in expected cash at closing', function () {
    $admin = cashRegisterAdmin();
    $session = CajaSession::query()->create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'opening_amount' => 500,
        'status' => 'open',
    ]);
    $client = Client::query()->create([
        'name' => 'Cliente caja',
        'phone' => '88880000',
    ]);
    Sale::query()->create([
        'invoice_number' => 'FAC-CAJA-001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'date' => now()->toDateString(),
        'payment_type' => 'cash',
        'status' => 'completed',
        'tax_included' => false,
        'tax_rate' => 0,
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
    ]);

    $this->actingAs($admin)
        ->post(route('arqueo.run'), [
            'date' => now()->toDateString(),
            'caja_session_id' => $session->id,
            'physical_counts' => [
                ['amount' => 100, 'qty' => 6],
            ],
        ])
        ->assertOk()
        ->assertSee('Fondo')
        ->assertSee('C$ 500.00')
        ->assertSee('Esperado');

    $arqueo = Arqueo::query()->firstOrFail();

    expect((float) $arqueo->cash_total)->toBe(600.0)
        ->and((float) $arqueo->difference)->toBe(0.0)
        ->and((float) $session->fresh()->opening_amount)->toBe(500.0)
        ->and($session->fresh()->status)->toBe('closed');
});

test('the same cash session cannot be closed twice', function () {
    $admin = cashRegisterAdmin();
    $session = CajaSession::query()->create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'opening_amount' => 100,
        'status' => 'open',
    ]);

    $payload = [
        'date' => now()->toDateString(),
        'caja_session_id' => $session->id,
        'physical_counts' => [['amount' => 100, 'qty' => 1]],
    ];

    $this->actingAs($admin)->post(route('arqueo.run'), $payload)->assertOk();

    $this->actingAs($admin)
        ->from(route('arqueo.index'))
        ->post(route('arqueo.run'), $payload)
        ->assertRedirect(route('arqueo.index'))
        ->assertSessionHasErrors('caja_session_id');

    expect(Arqueo::query()->where('caja_session_id', $session->id)->count())->toBe(1);
});

test('cash credit payments increase expected physical cash but transfers do not', function () {
    $admin = cashRegisterAdmin();
    $session = CajaSession::query()->create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'opening_amount' => 100,
        'status' => 'open',
    ]);
    $client = Client::query()->create(['name' => 'Cliente abono', 'phone' => '88881111']);
    CreditPayment::query()->create(['client_id' => $client->id, 'user_id' => $admin->id, 'amount' => 50, 'payment_type' => 'cash', 'payment_date' => now()]);
    CreditPayment::query()->create(['client_id' => $client->id, 'user_id' => $admin->id, 'amount' => 75, 'payment_type' => 'transfer', 'payment_date' => now()]);

    $this->actingAs($admin)->post(route('arqueo.run'), [
        'date' => now()->toDateString(),
        'caja_session_id' => $session->id,
        'physical_counts' => [['amount' => 50, 'qty' => 3]],
    ])->assertOk();

    expect((float) Arqueo::query()->firstOrFail()->cash_total)->toBe(150.0);
});

test('completed cash purchases reduce expected cash at closing', function () {
    $admin = cashRegisterAdmin();
    $supplier = Supplier::query()->create(['name' => 'Proveedor caja', 'status' => 'active']);
    $session = CajaSession::query()->create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'opening_amount' => 200,
        'status' => 'open',
    ]);
    Purchase::query()->create([
        'document_number' => 'COMP-CAJA-001',
        'supplier_id' => $supplier->id,
        'user_id' => $admin->id,
        'date' => now(),
        'status' => 'completed',
        'payment_type' => 'cash',
        'subtotal' => 50,
        'tax_total' => 0,
        'total' => 50,
        'caja_session_id' => $session->id,
    ]);

    $this->actingAs($admin)->post(route('arqueo.run'), [
        'date' => now()->toDateString(),
        'caja_session_id' => $session->id,
        'physical_counts' => [['amount' => 50, 'qty' => 3]],
    ])->assertOk()->assertSee('Compras en efectivo');

    expect((float) Arqueo::query()->firstOrFail()->cash_total)->toBe(150.0);
});
