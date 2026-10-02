<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function batteryAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
    openCashSessionFor($user);

    return $user;
}

function batteryPayload(array $extra = []): array
{
    return [
        'client_name' => 'Cliente Batería',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 11',
        'problem_description' => 'No carga',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'payment_type' => 'cash',
        ...$extra,
    ];
}

test('the battery percentage entered when receiving a repair is saved and printed everywhere', function () {
    $admin = batteryAdmin();

    $this->actingAs($admin)->get(route('reparaciones.create'))
        ->assertOk()
        ->assertSee('Batería al recibir (%)')
        ->assertSee('name="device_battery"', false);

    $this->actingAs($admin)->post(route('reparaciones.store'), batteryPayload(['device_battery' => 87]))
        ->assertSessionHasNoErrors();

    $order = RepairOrder::query()->firstOrFail();
    expect($order->device_battery)->toBe(87);

    foreach ([
        route('reparaciones.show', $order->id),
        route('reparaciones.pdf', $order->id),
        route('reparaciones.ticket', $order->id),
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertOk()->assertSeeText('Batería: 87%');
    }
});

test('the battery percentage is optional and nothing is printed when it is empty', function () {
    $admin = batteryAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), batteryPayload(['device_battery' => '']))
        ->assertSessionHasNoErrors();

    $order = RepairOrder::query()->firstOrFail();
    expect($order->device_battery)->toBeNull();

    foreach ([route('reparaciones.pdf', $order->id), route('reparaciones.ticket', $order->id)] as $url) {
        $this->actingAs($admin)->get($url)->assertOk()->assertDontSeeText('Batería:');
    }
});

test('a battery of zero percent is a real value and is printed', function () {
    $admin = batteryAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), batteryPayload(['device_battery' => 0]))
        ->assertSessionHasNoErrors();

    $order = RepairOrder::query()->firstOrFail();
    expect($order->device_battery)->toBe(0);
    $this->actingAs($admin)->get(route('reparaciones.ticket', $order->id))->assertSeeText('Batería: 0%');
});

test('the battery percentage must be between 0 and 100', function () {
    $admin = batteryAdmin();

    foreach ([101, -1, 'abc', 50.5] as $invalid) {
        $this->actingAs($admin)->post(route('reparaciones.store'), batteryPayload(['device_battery' => $invalid]))
            ->assertSessionHasErrors('device_battery');
    }

    expect(RepairOrder::query()->count())->toBe(0);
});

test('the battery percentage can be corrected when editing the order', function () {
    $admin = batteryAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), batteryPayload(['device_battery' => 60]));
    $order = RepairOrder::query()->firstOrFail();

    $this->actingAs($admin)->get(route('reparaciones.edit', $order))->assertOk()
        ->assertSee('name="device_battery"', false)
        ->assertSee('value="60"', false);

    $this->actingAs($admin)->put(route('reparaciones.update', $order), batteryPayload(['device_battery' => 92]))
        ->assertRedirect(route('reparaciones.show', $order));

    expect($order->fresh()->device_battery)->toBe(92);
});
