<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts database time values with seconds when editing a repair order', function () {
    $this->seed(ConfigurationSeeder::class);
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

    $this->actingAs($user)->post('/reparaciones', [
        'client_name' => 'María López',
        'device_brand' => 'Anillo',
        'device_model' => 'Oro 14K',
        'problem_description' => 'Ajustar talla',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => '2026-09-18',
        'received_time' => '09:30',
        'estimated_delivery_time' => '16:45',
        'payment_type' => 'transfer',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $order = RepairOrder::query()->firstOrFail();

    $response = $this->actingAs($user)->put(route('reparaciones.update', $order), [
        'client_name' => $order->client_name,
        'device_brand' => $order->device_brand,
        'device_model' => $order->device_model,
        'problem_description' => $order->problem_description,
        'status' => $order->status,
        'priority' => $order->priority,
        'received_date' => $order->received_date->format('Y-m-d'),
        'received_time' => '09:30:00',
        'estimated_delivery_time' => '16:45:00',
        'payment_type' => $order->payment_type,
    ]);

    $response->assertRedirect(route('reparaciones.show', $order))
        ->assertSessionHasNoErrors();

    expect(substr((string) $order->fresh()->received_time, 0, 5))->toBe('09:30')
        ->and(substr((string) $order->fresh()->estimated_delivery_time, 0, 5))->toBe('16:45');
});

it('shows a useful message when a repair time is invalid', function () {
    $this->seed(ConfigurationSeeder::class);
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

    $this->actingAs($user)->post('/reparaciones', [
        'client_name' => 'María López',
        'device_brand' => 'Anillo',
        'device_model' => 'Oro 14K',
        'problem_description' => 'Ajustar talla',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => '2026-09-18',
        'payment_type' => 'transfer',
    ])->assertRedirect();

    $order = RepairOrder::query()->firstOrFail();

    $this->actingAs($user)->from(route('reparaciones.edit', $order))->put(route('reparaciones.update', $order), [
        'client_name' => $order->client_name,
        'device_brand' => $order->device_brand,
        'device_model' => $order->device_model,
        'problem_description' => $order->problem_description,
        'status' => $order->status,
        'priority' => $order->priority,
        'received_date' => $order->received_date->format('Y-m-d'),
        'received_time' => 'hora-invalida',
        'payment_type' => $order->payment_type,
    ])->assertRedirect(route('reparaciones.edit', $order))
        ->assertSessionHasErrors([
            'received_time' => 'La hora de recepción debe tener el formato HH:MM.',
        ]);
});
