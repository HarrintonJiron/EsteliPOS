<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function deliveryAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
    openCashSessionFor($user);

    return $user;
}

function deliveryOrder(User $admin, string $status = 'ready'): RepairOrder
{
    return RepairOrder::create([
        'order_number' => 'REP-000901',
        'client_name' => 'Cliente Entrega',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 12',
        'problem_description' => 'Pantalla rota',
        'status' => $status,
        'priority' => 'normal',
        'received_date' => now()->subDays(3)->toDateString(),
        'user_id' => $admin->id,
        'labor_cost' => 20,
        'total' => 20,
    ]);
}

function deliveryUpdatePayload(array $extra = []): array
{
    return [
        'client_name' => 'Cliente Entrega',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 12',
        'problem_description' => 'Pantalla rota',
        'status' => 'delivered',
        'priority' => 'normal',
        'received_date' => now()->subDays(3)->toDateString(),
        'payment_type' => 'cash',
        'labor_cost' => 20,
        ...$extra,
    ];
}

test('delivering a repair can record the condition of the device and it shows in the ticket and pdf', function () {
    $admin = deliveryAdmin();
    $order = deliveryOrder($admin);
    $notes = "Se entrega con pantalla nueva.\nSin rayones ni golpes. Incluye cargador.";

    $this->actingAs($admin)->patch(route('reparaciones.status', $order->id), [
        'status' => 'delivered',
        'delivery_notes' => $notes,
    ])->assertSessionHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe('delivered')
        ->and($order->delivery_notes)->toBe($notes)
        ->and($order->delivered_date)->not->toBeNull();

    foreach ([
        route('reparaciones.ticket', $order->id) => 'DETALLES DEL EQUIPO AL ENTREGAR',
        route('reparaciones.pdf', $order->id) => 'Detalles del equipo al entregar',
    ] as $url => $heading) {
        $this->actingAs($admin)->get($url)->assertOk()
            ->assertSeeText($heading)
            ->assertSeeText('Se entrega con pantalla nueva.')
            ->assertSeeText('Sin rayones ni golpes. Incluye cargador.');
    }

    $this->actingAs($admin)->get(route('reparaciones.show', $order->id))->assertOk()
        ->assertSeeText('Se entrega con pantalla nueva.');
});

test('the delivery details can be left empty and then nothing extra is printed', function () {
    $admin = deliveryAdmin();
    $order = deliveryOrder($admin);

    $this->actingAs($admin)->patch(route('reparaciones.status', $order->id), [
        'status' => 'delivered',
        'delivery_notes' => '   ',
    ])->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe('delivered')
        ->and($order->fresh()->delivery_notes)->toBeNull();

    foreach ([route('reparaciones.ticket', $order->id), route('reparaciones.pdf', $order->id)] as $url) {
        $this->actingAs($admin)->get($url)->assertOk()
            ->assertDontSeeText('DETALLES DEL EQUIPO AL ENTREGAR', false)
            ->assertDontSeeText('Detalles del equipo al entregar');
    }
});

test('a later status change without text does not erase the delivery details', function () {
    $admin = deliveryAdmin();
    $order = deliveryOrder($admin);

    $this->actingAs($admin)->patch(route('reparaciones.status', $order->id), [
        'status' => 'delivered',
        'delivery_notes' => 'Entregado sin daños visibles.',
    ]);

    $this->actingAs($admin)->patch(route('reparaciones.status', $order->id), [
        'status' => 'delivered',
    ])->assertSessionHasNoErrors();

    expect($order->fresh()->delivery_notes)->toBe('Entregado sin daños visibles.');
});

test('the edit form saves, shows and clears the delivery details', function () {
    $admin = deliveryAdmin();
    $order = deliveryOrder($admin, 'delivered');

    $this->actingAs($admin)->get(route('reparaciones.edit', $order))->assertOk()
        ->assertSee('name="delivery_notes"', false);

    $this->actingAs($admin)->put(route('reparaciones.update', $order), deliveryUpdatePayload([
        'delivery_notes' => 'Equipo con detalle leve en el marco, ya existía al recibirlo.',
    ]))->assertRedirect(route('reparaciones.show', $order));

    expect($order->fresh()->delivery_notes)->toBe('Equipo con detalle leve en el marco, ya existía al recibirlo.');

    $this->actingAs($admin)->get(route('reparaciones.edit', $order))->assertOk()
        ->assertSee('Equipo con detalle leve en el marco', false);

    $this->actingAs($admin)->put(route('reparaciones.update', $order), deliveryUpdatePayload([
        'delivery_notes' => '',
    ]))->assertRedirect(route('reparaciones.show', $order));

    expect($order->fresh()->delivery_notes)->toBeNull();
});

test('the delivery details are limited in length', function () {
    $admin = deliveryAdmin();
    $order = deliveryOrder($admin);

    $this->actingAs($admin)->patch(route('reparaciones.status', $order->id), [
        'status' => 'delivered',
        'delivery_notes' => str_repeat('a', 2001),
    ])->assertSessionHasErrors('delivery_notes');

    expect($order->fresh()->status)->toBe('ready');
});
