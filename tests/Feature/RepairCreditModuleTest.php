<?php

use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\RepairCreditPayment;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\CreditService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function creditModuleAdmin(bool $withCash = true): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

    if ($withCash) {
        openCashSessionFor($user);
    }

    return $user;
}

function creditClient(array $overrides = []): Client
{
    return Client::create($overrides + ['name' => 'Cliente Crédito', 'credit_enabled' => true, 'credit_limit' => 1000, 'credit_days' => 30, 'status' => 'active']);
}

function creditRepair(User $admin, Client $client, array $overrides = []): RepairOrder
{
    static $n = 0;
    $n++;

    return RepairOrder::create($overrides + [
        'order_number' => 'REP-8'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
        'client_id' => $client->id,
        'client_name' => $client->name,
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 11',
        'problem_description' => 'Pantalla',
        'status' => 'delivered',
        'priority' => 'normal',
        'received_date' => now()->subDays(20)->toDateString(),
        'due_date' => now()->addDays(10)->toDateString(),
        'user_id' => $admin->id,
        'labor_cost' => 100,
        'total' => 100,
        'advance_payment' => 20,
        'payment_type' => 'credit',
        'payment_status' => 'partial',
    ]);
}

function creditSale(User $admin, Client $client, float $total, array $overrides = []): Sale
{
    static $n = 0;
    $n++;

    return Sale::create($overrides + [
        'invoice_number' => 'FAC-C'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'date' => now()->subDays(5)->toDateString(),
        'due_date' => now()->addDays(25)->toDateString(),
        'payment_type' => 'credit',
        'status' => 'pending',
        'tax_included' => false,
        'tax_rate' => 0,
        'subtotal' => $total,
        'tax_total' => 0,
        'total' => $total,
    ]);
}

function abonoPayload(Client $client, array $extra = []): array
{
    return [
        'client_id' => $client->id,
        'amount' => 50,
        'payment_type' => 'transfer',
        'request_token' => (string) Str::uuid(),
        ...$extra,
    ];
}

test('a repair created on credit shows up in the credit module under repairs and counts against the limit', function () {
    $admin = creditModuleAdmin();
    $client = creditClient(['name' => 'María Taller', 'credit_limit' => 500]);
    $repair = creditRepair($admin, $client);

    expect(app(CreditService::class)->pendingDebt($client))->toBe(80.0)
        ->and(app(CreditService::class)->availableCredit($client))->toBe(420.0);

    $this->actingAs($admin)->get(route('creditos.index', ['type' => 'repairs']))->assertOk()
        ->assertSeeText('Créditos de reparaciones')
        ->assertSeeText('María Taller')
        ->assertSeeText($repair->order_number)
        ->assertSeeText('Reparaciones C$ 80.00');

    $this->actingAs($admin)->get(route('creditos.index', ['type' => 'sales']))->assertOk()
        ->assertDontSeeText('María Taller');

    $this->actingAs($admin)->get(route('creditos.index'))->assertOk()
        ->assertSeeText('María Taller');

    $this->actingAs($admin)->get(route('creditos.show', $client->id))->assertOk()
        ->assertSeeText('Créditos de reparaciones')
        ->assertSeeText($repair->order_number)
        ->assertSeeText('Abonar');
});

test('creating a repair on credit from the repair form loads it in the credit module', function () {
    $admin = creditModuleAdmin();
    $client = creditClient(['name' => 'Cliente Formulario']);

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        'client_id' => $client->id,
        'client_name' => $client->name,
        'device_brand' => 'Samsung',
        'device_model' => 'A54',
        'problem_description' => 'No carga',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'labor_cost' => 120,
        'payment_type' => 'credit',
        'due_date' => now()->addDays(15)->toDateString(),
    ])->assertSessionHasNoErrors();

    $order = RepairOrder::query()->firstOrFail();
    expect($order->payment_type)->toBe('credit')
        ->and($order->paymentState())->toBe('credit')
        ->and(app(CreditService::class)->pendingRepairDebt($client))->toBe(120.0);

    $this->actingAs($admin)->get(route('creditos.index', ['type' => 'repairs']))->assertOk()
        ->assertSeeText('Cliente Formulario')
        ->assertSeeText($order->order_number);
});

test('an abono from the credit module reduces a repair credit even when the client owes no invoices', function () {
    $admin = creditModuleAdmin();
    $client = creditClient();
    $repair = creditRepair($admin, $client);

    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 50]))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error');

    $repair->refresh();
    expect($repair->balance())->toBe(30.0)
        ->and($repair->payment_status)->toBe('partial')
        ->and(RepairCreditPayment::query()->count())->toBe(1)
        ->and(CreditPayment::query()->count())->toBe(0)
        ->and(app(CreditService::class)->pendingDebt($client))->toBe(30.0);

    $payment = RepairCreditPayment::query()->firstOrFail();
    expect($payment->payment_type)->toBe('transfer')->and($payment->user_id)->toBe($admin->id);
    expect(Sale::query()->where('repair_order_id', $repair->id)->count())->toBe(1);
});

test('paying the full balance closes the repair credit and removes it from the credit list', function () {
    $admin = creditModuleAdmin();
    $client = creditClient(['name' => 'Cliente Que Paga']);
    $repair = creditRepair($admin, $client);

    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 80, 'payment_type' => 'cash']))
        ->assertSessionHasNoErrors()->assertSessionMissing('error');

    $repair->refresh();
    expect($repair->payment_status)->toBe('paid')
        ->and($repair->paymentState())->toBe('paid')
        ->and(app(CreditService::class)->pendingDebt($client))->toBe(0.0);

    $this->actingAs($admin)->get(route('creditos.index', ['type' => 'repairs']))->assertOk()
        ->assertDontSeeText('Cliente Que Paga');
});

test('an abono can target a single repair credit', function () {
    $admin = creditModuleAdmin();
    $client = creditClient();
    $first = creditRepair($admin, $client);
    $second = creditRepair($admin, $client, ['total' => 200, 'labor_cost' => 200, 'advance_payment' => 0]);

    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 60, 'apply_to' => 'repair:'.$second->id]))
        ->assertSessionHasNoErrors()->assertSessionMissing('error');

    expect($first->fresh()->balance())->toBe(80.0)
        ->and($second->fresh()->balance())->toBe(140.0);

    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 500, 'apply_to' => 'repair:'.$first->id]))
        ->assertSessionHas('error');

    expect($first->fresh()->balance())->toBe(80.0);
});

test('an automatic abono pays invoices first and the rest goes to repairs', function () {
    $admin = creditModuleAdmin();
    $client = creditClient();
    creditSale($admin, $client, 100);
    $repair = creditRepair($admin, $client);

    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 130]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'facturas $ 100.00') && str_contains($message, 'reparaciones $ 30.00'));

    expect((float) CreditPayment::query()->sum('amount'))->toBe(100.0)
        ->and($repair->fresh()->balance())->toBe(50.0)
        ->and(app(CreditService::class)->pendingDebt($client))->toBe(50.0);
});

test('an abono only to repairs cannot exceed the repair balance', function () {
    $admin = creditModuleAdmin();
    $client = creditClient();
    creditSale($admin, $client, 100);
    creditRepair($admin, $client);

    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 120, 'apply_to' => 'repairs']))
        ->assertSessionHas('error');

    expect(RepairCreditPayment::query()->count())->toBe(0)->and(CreditPayment::query()->count())->toBe(0);
});

test('the same abono form submitted twice is recorded once', function () {
    $admin = creditModuleAdmin();
    $client = creditClient();
    creditRepair($admin, $client);
    $payload = abonoPayload($client, ['amount' => 40]);

    $this->actingAs($admin)->post(route('creditos.store'), $payload)->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('creditos.store'), $payload)->assertSessionHas('success', fn ($message) => str_contains($message, 'no se duplicó'));

    expect(RepairCreditPayment::query()->count())->toBe(1);
});

test('a cash abono to a repair credit needs an open register', function () {
    $admin = creditModuleAdmin(withCash: false);
    $client = creditClient();
    $repair = creditRepair($admin, $client);

    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 40, 'payment_type' => 'cash']))
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'caja'));

    expect(RepairCreditPayment::query()->count())->toBe(0)->and($repair->fresh()->balance())->toBe(80.0);
});

test('a repair-only abono can print its own receipt', function () {
    $admin = creditModuleAdmin();
    $client = creditClient();
    $repair = creditRepair($admin, $client);

    $response = $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 30, 'print' => 1]));

    $payment = RepairCreditPayment::query()->firstOrFail();
    $response->assertRedirect(route('creditos.repair-receipt', $payment->id));

    $this->actingAs($admin)->get(route('creditos.repair-receipt', $payment->id))->assertOk()
        ->assertSeeText('RECIBO DE ABONO')
        ->assertSeeText($repair->order_number)
        ->assertSeeText('$ 30.00')
        ->assertSeeText('Saldo pendiente')
        ->assertSeeText('$ 50.00');
});

test('overdue repair credits appear in the overdue list, the mora and the aging report', function () {
    $admin = creditModuleAdmin();
    $client = creditClient(['name' => 'Cliente Moroso', 'mora_enabled' => true, 'mora_rate' => 1, 'mora_grace_days' => 0]);
    $repair = creditRepair($admin, $client, ['due_date' => now()->subDays(10)->toDateString()]);

    $this->actingAs($admin)->get(route('creditos.overdue'))->assertOk()
        ->assertSeeText('Reparaciones a crédito vencidas')
        ->assertSeeText($repair->order_number)
        ->assertSeeText('10 días');

    $breakdown = app(CreditService::class)->moraBreakdown($client);
    expect($breakdown)->toHaveCount(1)
        ->and($breakdown[0]['type'])->toBe('repair')
        ->and($breakdown[0]['days_late'])->toBe(10)
        ->and($breakdown[0]['mora'])->toBe(8.0)
        ->and(app(CreditService::class)->moraForClient($client))->toBe(8.0);

    expect(app(CreditService::class)->agingReport()['days_1_30'])->toBe(80.0);

    $this->actingAs($admin)->get(route('creditos.show', $client->id))->assertOk()
        ->assertSeeText('Mora Acumulada')
        ->assertSeeText($repair->order_number);
});

test('the statement, report and export include repair credits', function () {
    $admin = creditModuleAdmin();
    $client = creditClient(['name' => 'Cliente Reporte']);
    $repair = creditRepair($admin, $client);
    $this->actingAs($admin)->post(route('creditos.store'), abonoPayload($client, ['amount' => 25]))->assertSessionHasNoErrors();

    $this->actingAs($admin)->get(route('creditos.statement', $client->id))->assertOk()
        ->assertSeeText('Reparaciones a crédito')
        ->assertSeeText($repair->order_number)
        ->assertSeeText('$ 25.00');

    $this->actingAs($admin)->get(route('creditos.report'))->assertOk()
        ->assertSeeText('Créditos de Reparaciones Otorgados')
        ->assertSeeText('Abonos a Reparaciones')
        ->assertSeeText($repair->order_number);

    $csv = $this->actingAs($admin)->get(route('creditos.export'))->assertOk()->getContent();
    expect($csv)->toContain('Saldo Reparaciones')
        ->and($csv)->toContain('Cliente Reporte')
        ->and($csv)->toContain('55.00');
});

test('the credit search returns the pending repairs of the client', function () {
    $admin = creditModuleAdmin();
    $client = creditClient(['name' => 'Cliente Buscar']);
    $repair = creditRepair($admin, $client);

    $json = $this->actingAs($admin)->getJson(route('creditos.search', ['q' => 'Cliente Buscar']))->assertOk()->json();

    expect($json[0]['pending_repairs'][0]['order_number'])->toBe($repair->order_number)
        ->and($json[0]['pending_repairs'][0]['balance'])->toEqual(80.0)
        ->and($json[0]['credit_summary']['repairs_balance'])->toEqual(80.0);
});

test('the repair list can filter credit repairs and the repair page links to the credit account', function () {
    $admin = creditModuleAdmin();
    $client = creditClient(['name' => 'Cliente En Lista']);
    $credit = creditRepair($admin, $client);
    $cash = creditRepair($admin, $client, ['payment_type' => 'cash', 'client_name' => 'Pago Contado Lista']);

    $this->actingAs($admin)->get(route('reparaciones.index', ['payment_status' => 'credit']))->assertOk()
        ->assertSeeText($credit->order_number)
        ->assertDontSeeText('Pago Contado Lista');

    $this->actingAs($admin)->get(route('reparaciones.show', $credit->id))->assertOk()
        ->assertSee(route('creditos.show', $client->id), false)
        ->assertSeeText('Ver cuenta de crédito del cliente');
});

test('payments made through the repair page stay in sync with the credit module', function () {
    $admin = creditModuleAdmin();
    $client = creditClient();
    $repair = creditRepair($admin, $client);

    $this->actingAs($admin)->post(route('reparaciones.credit-payments.store', $repair->id), ['amount' => 30, 'payment_type' => 'cash'])
        ->assertSessionHasNoErrors();

    expect(app(CreditService::class)->pendingDebt($client))->toBe(50.0);

    $this->actingAs($admin)->get(route('creditos.show', $client->id))->assertOk()->assertSeeText('Abonos a reparaciones');
});
