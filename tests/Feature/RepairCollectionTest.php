<?php

use App\Models\CajaSession;
use App\Models\Client;
use App\Models\RepairCreditPayment;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\CreditService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function collectAdmin(bool $withCash = true): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

    if ($withCash) {
        openCashSessionFor($user);
    }

    return $user;
}

function collectOrder(User $admin, array $overrides = []): RepairOrder
{
    static $counter = 0;
    $counter++;

    return RepairOrder::create($overrides + [
        'order_number' => 'REP-9'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
        'client_name' => 'Cliente Cobro',
        'device_brand' => 'Samsung',
        'device_model' => 'A54',
        'problem_description' => 'Pantalla',
        'status' => 'ready',
        'priority' => 'normal',
        'received_date' => now()->subDays(2)->toDateString(),
        'user_id' => $admin->id,
        'labor_cost' => 100,
        'total' => 100,
        'advance_payment' => 20,
        'payment_type' => 'cash',
        'payment_status' => 'partial',
    ]);
}

test('collecting the full balance in cash pays the order, records the cash sale and can deliver it', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'cash',
        'amount' => 80,
        'received' => 100,
        'mark_delivered' => 1,
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'Cambio: $ 20.00') && str_contains($message, 'quedó pagada'));

    $order->refresh();
    expect($order->status)->toBe('delivered')
        ->and($order->delivered_date)->not->toBeNull()
        ->and($order->payment_status)->toBe('paid')
        ->and($order->balance())->toBe(0.0)
        ->and($order->paymentState())->toBe('paid');

    $payment = RepairCreditPayment::query()->where('repair_order_id', $order->id)->firstOrFail();
    expect((float) $payment->amount)->toBe(80.0)->and($payment->payment_type)->toBe('cash');

    $sale = Sale::query()->where('repair_order_id', $order->id)->firstOrFail();
    expect((float) $sale->total)->toBe(80.0)->and($sale->payment_type)->toBe('cash')->and($sale->caja_session_id)->not->toBeNull();
});

test('a partial collection leaves the order as partially paid and never as paid', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'cash',
        'amount' => 30,
        'mark_delivered' => 1,
    ])->assertSessionHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe('delivered')
        ->and($order->payment_status)->toBe('partial')
        ->and($order->balance())->toBe(50.0)
        ->and($order->paymentState())->toBe('partial')
        ->and($order->isDeliveredWithBalance())->toBeTrue();

    foreach ([route('reparaciones.ticket', $order->id), route('reparaciones.pdf', $order->id)] as $url) {
        $this->actingAs($admin)->get($url)->assertOk()
            ->assertSeeText('SALDO PENDIENTE')
            ->assertDontSeeText('PAGADO');
    }
    $this->actingAs($admin)->get(route('reparaciones.ticket', $order->id))->assertSeeText('ENTREGADO · PAGO PENDIENTE');

    $this->actingAs($admin)->get(route('reparaciones.show', $order->id))->assertOk()
        ->assertSeeText('Entregada sin pagar completo')
        ->assertSeeText('Abono parcial')
        ->assertSee('id="openCollect"', false);
});

test('an amount above the balance is rejected and nothing is recorded', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'cash',
        'amount' => 80.01,
    ])->assertSessionHas('error');

    expect(RepairCreditPayment::query()->count())->toBe(0)
        ->and(Sale::query()->where('repair_order_id', $order->id)->count())->toBe(0)
        ->and($order->fresh()->balance())->toBe(80.0);
});

test('cash without an open register is rejected and leaves no payment behind', function () {
    $admin = collectAdmin(withCash: false);
    $order = collectOrder($admin);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'cash',
        'amount' => 80,
    ])->assertSessionHas('error', fn ($message) => str_contains($message, 'caja'));

    expect(RepairCreditPayment::query()->count())->toBe(0)
        ->and($order->fresh()->balance())->toBe(80.0)
        ->and(CajaSession::query()->count())->toBe(0);
});

test('cash received below the amount is rejected', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'cash',
        'amount' => 80,
        'received' => 50,
    ])->assertSessionHas('error');

    expect(RepairCreditPayment::query()->count())->toBe(0);
});

test('card and transfer collections do not need an open register', function () {
    $admin = collectAdmin(withCash: false);
    $order = collectOrder($admin);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'card',
        'amount' => 40,
        'reference_number' => 'AUT-123',
    ])->assertSessionHasNoErrors()->assertSessionMissing('error');

    $payment = RepairCreditPayment::query()->firstOrFail();
    expect($payment->payment_type)->toBe('card')->and($payment->reference_number)->toBe('AUT-123');
    expect(Sale::query()->where('repair_order_id', $order->id)->value('payment_type'))->toBe('transfer');
});

test('an order that is not ready or delivered cannot be collected from the quick form', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin, ['status' => 'in_repair']);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'cash',
        'amount' => 10,
    ])->assertSessionHas('error');

    $this->actingAs($admin)->get(route('reparaciones.show', $order->id))->assertOk()
        ->assertDontSee('id="openCollect"', false);
});

test('the collect button only shows while there is a balance to collect', function () {
    $admin = collectAdmin();
    $ready = collectOrder($admin);
    $paid = collectOrder($admin, ['advance_payment' => 100, 'payment_status' => 'paid']);

    $this->actingAs($admin)->get(route('reparaciones.show', $ready->id))->assertOk()
        ->assertSee('id="openCollect"', false)
        ->assertSee('id="collectModal"', false);

    $this->actingAs($admin)->get(route('reparaciones.show', $paid->id))->assertOk()
        ->assertDontSee('id="openCollect"', false);
});

test('a ready order can be delivered on credit to a client with credit and the debt is tracked', function () {
    $admin = collectAdmin();
    $client = Client::create(['name' => 'Cliente Crédito', 'credit_enabled' => true, 'credit_limit' => 500, 'credit_days' => 15]);
    $order = collectOrder($admin, ['client_id' => $client->id]);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'credit',
        'mark_delivered' => 1,
    ])->assertSessionHasNoErrors()->assertSessionMissing('error');

    $order->refresh();
    expect($order->payment_type)->toBe('credit')
        ->and($order->status)->toBe('delivered')
        ->and($order->due_date?->toDateString())->toBe(now()->addDays(15)->toDateString())
        ->and($order->paymentState())->toBe('credit')
        ->and($order->payment_status)->toBe('partial')
        ->and(app(CreditService::class)->pendingRepairDebt($client))->toBe(80.0)
        ->and(RepairCreditPayment::query()->count())->toBe(0)
        ->and(Sale::query()->where('repair_order_id', $order->id)->count())->toBe(0);

    $this->actingAs($admin)->get(route('reparaciones.ticket', $order->id))->assertOk()
        ->assertSeeText('A CRÉDITO · vence')
        ->assertSeeText('SALDO PENDIENTE');
});

test('credit is refused when the client has no credit or the limit is exceeded', function () {
    $admin = collectAdmin();
    $noCredit = Client::create(['name' => 'Sin crédito', 'credit_enabled' => false]);
    $tight = Client::create(['name' => 'Límite corto', 'credit_enabled' => true, 'credit_limit' => 50]);

    $first = collectOrder($admin, ['client_id' => $noCredit->id]);
    $this->actingAs($admin)->post(route('reparaciones.collect', $first->id), ['method' => 'credit'])
        ->assertSessionHas('error');
    expect($first->fresh()->payment_type)->toBe('cash');

    $second = collectOrder($admin, ['client_id' => $tight->id]);
    $this->actingAs($admin)->post(route('reparaciones.collect', $second->id), ['method' => 'credit'])
        ->assertSessionHas('error');
    expect($second->fresh()->payment_type)->toBe('cash');
});

test('credit for an order without a client asks for one and links the chosen client', function () {
    $admin = collectAdmin();
    $client = Client::create(['name' => 'Cliente Elegido', 'credit_enabled' => true, 'credit_limit' => 0]);
    $order = collectOrder($admin, ['client_id' => null]);

    $this->actingAs($admin)->get(route('reparaciones.show', $order->id))->assertOk()
        ->assertSee('name="client_id"', false)
        ->assertSee('Cliente Elegido');

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), ['method' => 'credit'])
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'cliente'));
    expect($order->fresh()->payment_type)->toBe('cash');

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'credit',
        'client_id' => $client->id,
        'due_date' => now()->addDays(10)->toDateString(),
    ])->assertSessionHasNoErrors()->assertSessionMissing('error');

    $order->refresh();
    expect($order->client_id)->toBe($client->id)
        ->and($order->payment_type)->toBe('credit')
        ->and($order->due_date?->toDateString())->toBe(now()->addDays(10)->toDateString());
});

test('an order already on credit cannot be granted credit again but accepts collections', function () {
    $admin = collectAdmin();
    $client = Client::create(['name' => 'Ya con crédito', 'credit_enabled' => true, 'credit_limit' => 0]);
    $order = collectOrder($admin, ['client_id' => $client->id, 'payment_type' => 'credit', 'due_date' => now()->addDays(5)->toDateString()]);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), ['method' => 'credit'])
        ->assertSessionHas('error');

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), ['method' => 'cash', 'amount' => 80])
        ->assertSessionHasNoErrors()->assertSessionMissing('error');

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and(app(CreditService::class)->pendingRepairDebt($client))->toBe(0.0);
});

test('payments on a non credit order do not appear in the client credit statement', function () {
    $admin = collectAdmin();
    $client = Client::create(['name' => 'Cliente Contado', 'credit_enabled' => true]);
    $order = collectOrder($admin, ['client_id' => $client->id]);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), ['method' => 'cash', 'amount' => 80])
        ->assertSessionHasNoErrors();

    $row = app(CreditService::class)->clientsWithDebt()->firstWhere('id', $client->id);

    expect((float) $row['total_paid'])->toBe(0.0)
        ->and($this->actingAs($admin)->get(route('creditos.show', $client->id))->assertOk()->viewData('repairPayments'))->toHaveCount(0);
});

test('an order without an amount is never shown as paid', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin, ['total' => 0, 'labor_cost' => 0, 'advance_payment' => 0, 'status' => 'received', 'payment_status' => 'pending']);

    expect($order->paymentState())->toBe('no_charge')
        ->and($order->paymentStateLabel())->toBe('Sin cargo')
        ->and(RepairOrder::paymentStatusFor(0, 0))->toBe('pending');
});

test('delivering from the quick status form with a pending balance warns and keeps it unpaid', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin);

    $this->actingAs($admin)->patch(route('reparaciones.status', $order->id), ['status' => 'delivered'])
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'saldo pendiente de $ 80.00'));

    $order->refresh();
    expect($order->status)->toBe('delivered')
        ->and($order->payment_status)->toBe('partial')
        ->and($order->isDeliveredWithBalance())->toBeTrue();
});

test('the repair list can filter delivered orders that still owe money', function () {
    $admin = collectAdmin();
    $owing = collectOrder($admin, ['status' => 'delivered', 'client_name' => 'Debe Dinero']);
    collectOrder($admin, ['status' => 'delivered', 'client_name' => 'Ya Pagó', 'advance_payment' => 100, 'payment_status' => 'paid']);
    collectOrder($admin, ['status' => 'ready', 'client_name' => 'Aún No Entrega']);

    $this->actingAs($admin)->get(route('reparaciones.index', ['payment_status' => 'delivered_unpaid']))->assertOk()
        ->assertSeeText('Debe Dinero')
        ->assertDontSeeText('Ya Pagó')
        ->assertDontSeeText('Aún No Entrega');
});

test('the weekly report counts what was collected after the advance', function () {
    $admin = collectAdmin();
    $order = collectOrder($admin, ['status' => 'delivered', 'delivered_date' => now()->toDateString(), 'delivered_time' => '10:00']);

    RepairCreditPayment::create([
        'repair_order_id' => $order->id,
        'client_id' => null,
        'user_id' => $admin->id,
        'amount' => 30,
        'payment_date' => now(),
        'payment_type' => 'cash',
    ]);

    $response = $this->actingAs($admin)->get(route('reparaciones.informe-semanal'))->assertOk();
    $summary = $response->viewData('summary');

    expect($summary['collected_total'])->toBe(50.0)->and($summary['balance_total'])->toBe(50.0);
});

test('two walk-in customers with the same name do not share one client record', function () {
    $admin = collectAdmin();

    $first = collectOrder($admin, ['client_name' => 'Juan Perez', 'client_phone' => '88880001']);
    $second = collectOrder($admin, ['client_name' => 'Juan Perez', 'client_phone' => '88880002']);

    foreach ([$first, $second] as $order) {
        $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
            'method' => 'cash',
            'amount' => 80,
        ])->assertSessionHasNoErrors();
    }

    $clients = Client::query()->where('name', 'Juan Perez')->get();
    expect($clients)->toHaveCount(2)
        ->and($clients->pluck('phone')->sort()->values()->all())->toBe(['88880001', '88880002']);
});

test('a repair collection never attaches itself to an existing credit client by name', function () {
    $admin = collectAdmin();

    $creditClient = Client::create([
        'name' => 'Maria Lopez',
        'phone' => '77770000',
        'status' => 'active',
        'credit_enabled' => true,
        'credit_limit' => 500,
    ]);

    $order = collectOrder($admin, ['client_name' => 'Maria Lopez', 'client_phone' => '77770000']);

    $this->actingAs($admin)->post(route('reparaciones.collect', $order->id), [
        'method' => 'cash',
        'amount' => 80,
    ])->assertSessionHasNoErrors();

    $sale = Sale::query()->where('repair_order_id', $order->id)->firstOrFail();
    expect($sale->client_id)->not->toBe($creditClient->id);

    $creditClient->refresh();
    expect(Client::query()->where('name', 'Maria Lopez')->count())->toBe(2);
});
