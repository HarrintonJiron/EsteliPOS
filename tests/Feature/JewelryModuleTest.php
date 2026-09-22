<?php

use App\Models\Module;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function jewelryAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([$role->id]);
    Module::query()->where('slug', 'joyeria')->update(['is_active' => true]);
    Module::flushModuleCache();

    return $user;
}

function workshopPayload(string $client, string $brand, string $model): array
{
    return [
        'client_name' => $client,
        'device_brand' => $brand,
        'device_model' => $model,
        'problem_description' => 'Trabajo solicitado por el cliente',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'payment_type' => 'cash',
    ];
}

test('jewelry is an independent configurable module with isolated orders', function () {
    $admin = jewelryAdmin();

    $this->actingAs($admin)
        ->post(route('reparaciones.store'), workshopPayload('Cliente reparación', 'Samsung', 'A54'))
        ->assertRedirect();
    $this->actingAs($admin)
        ->post(route('joyeria.store'), workshopPayload('Cliente joyería', 'Anillo', 'Oro 14K'))
        ->assertRedirect();

    $repair = RepairOrder::query()->where('order_type', 'repair')->firstOrFail();
    $jewelry = RepairOrder::query()->where('order_type', 'jewelry')->firstOrFail();

    expect($repair->order_number)->toStartWith('REP-')
        ->and($jewelry->order_number)->toStartWith('JOY-')
        ->and($jewelry->device_password)->toBeNull();

    $this->actingAs($admin)->get(route('reparaciones.index'))
        ->assertOk()
        ->assertSee('Cliente reparación')
        ->assertDontSee('Cliente joyería');

    $this->actingAs($admin)->get(route('joyeria.index'))
        ->assertOk()
        ->assertSee('Joyería')
        ->assertSee('Cliente joyería')
        ->assertDontSee('Cliente reparación');
});

test('orders cannot be opened through the other workshop module', function () {
    $admin = jewelryAdmin();
    $this->actingAs($admin)->post(route('joyeria.store'), workshopPayload('Cliente aislado', 'Cadena', 'Plata'));
    $order = RepairOrder::query()->where('order_type', 'jewelry')->firstOrFail();

    $this->actingAs($admin)->get(route('reparaciones.show', $order))->assertNotFound();
    $this->actingAs($admin)->get(route('joyeria.show', $order))->assertOk();
});
