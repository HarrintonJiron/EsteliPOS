<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('editing normalizes repair times stored with seconds', function () {
    $this->seed(ConfigurationSeeder::class);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));

    $payload = [
        'client_name' => 'Cliente Horas QA',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'problem_description' => 'Pantalla quebrada',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'payment_type' => 'cash',
    ];

    $this->actingAs($admin)->post(route('reparaciones.store'), $payload)
        ->assertRedirect();

    $order = RepairOrder::query()->firstOrFail();
    $order->update([
        'received_time' => '08:30:00',
        'estimated_delivery_time' => '12:15:00',
        'delivered_time' => '16:45:00',
    ]);

    $this->actingAs($admin)->get(route('reparaciones.edit', $order))
        ->assertOk()
        ->assertSee('name="received_time" value="08:30"', false)
        ->assertSee('name="estimated_delivery_time" value="12:15"', false)
        ->assertSee('name="delivered_time" value="16:45"', false);

    $this->actingAs($admin)->put(route('reparaciones.update', $order), [
        ...$payload,
        'received_time' => '08:30:00',
        'estimated_delivery_time' => '12:15:00',
        'delivered_time' => '16:45:00',
    ])->assertRedirect(route('reparaciones.show', $order));

    $order->refresh();

    expect(substr((string) $order->received_time, 0, 5))->toBe('08:30')
        ->and(substr((string) $order->estimated_delivery_time, 0, 5))->toBe('12:15')
        ->and(substr((string) $order->delivered_time, 0, 5))->toBe('16:45');
});

test('editing accepts times submitted with seconds, fractional seconds, or 12-hour AM/PM notation', function () {
    $this->seed(ConfigurationSeeder::class);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));

    $payload = [
        'client_name' => 'Cliente Horas QA 2',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 12',
        'problem_description' => 'No enciende',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'payment_type' => 'cash',
        'received_time' => '08:30:00',
    ];

    $this->actingAs($admin)->post(route('reparaciones.store'), $payload)->assertRedirect();

    $order = RepairOrder::query()->firstOrFail();

    $this->actingAs($admin)->put(route('reparaciones.update', $order), [
        ...$payload,
        'received_time' => '2:08 PM',
        'estimated_delivery_time' => '14:08:00.000000',
        'delivered_time' => '04:45:30 PM',
    ])->assertRedirect(route('reparaciones.show', $order))
        ->assertSessionHasNoErrors();

    $order->refresh();

    expect(substr((string) $order->received_time, 0, 5))->toBe('14:08')
        ->and(substr((string) $order->estimated_delivery_time, 0, 5))->toBe('14:08')
        ->and(substr((string) $order->delivered_time, 0, 5))->toBe('16:45');
});
