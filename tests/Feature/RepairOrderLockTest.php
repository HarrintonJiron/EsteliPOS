<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('repair order lock handling', function () {
    it('renders repair forms and exposes their brand and service endpoints', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $order = RepairOrder::create([
            'client_name' => 'Cliente de prueba',
            'device_brand' => 'Samsung',
            'device_model' => 'A54',
            'problem_description' => 'No enciende',
            'received_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->get(route('reparaciones.create'))->assertOk();
        $this->actingAs($user)->get(route('reparaciones.edit', $order))->assertOk();

        $this->actingAs($user)->postJson(route('device-brands.store'), ['name' => 'Marca nueva'])
            ->assertCreated()
            ->assertJsonPath('name', 'Marca nueva');
        $this->actingAs($user)->postJson(route('repair-services.store'), [
            'name' => 'Servicio nuevo',
            'price' => 125,
        ])->assertCreated()->assertJsonPath('name', 'Servicio nuevo');
    });

    it('stores a repair order with a pattern lock', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $response = $this->actingAs($user)->post('/reparaciones', [
            'client_name' => 'Ana García',
            'device_brand' => 'Samsung',
            'device_model' => 'Galaxy A54',
            'problem_description' => 'No enciende y muestra pantalla negra',
            'status' => 'received',
            'priority' => 'normal',
            'received_date' => now()->toDateString(),
            'received_time' => '08:35',
            'estimated_date' => now()->addDays(2)->toDateString(),
            'estimated_time' => '15:30',
            'include_warranty_policy' => '1',
            'warranty_days' => 45,
            'warranty_policy' => 'Garantía válida únicamente para el servicio realizado.',
            'payment_type' => 'cash',
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_orders', [
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
            'include_warranty_policy' => 1,
            'warranty_days' => 45,
            'warranty_policy' => 'Garantía válida únicamente para el servicio realizado.',
        ]);
        expect(substr((string) RepairOrder::latest('id')->value('received_time'), 0, 5))->toBe('08:35');
        expect(substr((string) RepairOrder::latest('id')->value('estimated_time'), 0, 5))->toBe('15:30');
        $this->actingAs($user)
            ->get(route('reparaciones.ticket', RepairOrder::latest('id')->value('id')))
            ->assertOk()
            ->assertSee('GARANTÍA: 45 DÍAS')
            ->assertSee('Garantía válida únicamente para el servicio realizado.');
    });

    it('records the delivery date and time when the status changes to delivered', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $order = RepairOrder::create([
            'client_name' => 'Cliente de prueba',
            'device_brand' => 'Apple',
            'device_model' => 'iPhone 13',
            'problem_description' => 'Pantalla quebrada',
            'received_date' => now()->toDateString(),
            'received_time' => '09:10',
        ]);

        $this->travelTo(Carbon::parse('2026-08-02 16:45:00', 'America/Managua'));

        $this->actingAs($user)
            ->patch(route('reparaciones.status', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $deliveredOrder = $order->fresh();

        expect($deliveredOrder->status)->toBe('delivered')
            ->and($deliveredOrder->delivered_date->format('Y-m-d'))->toBe('2026-08-02')
            ->and(substr((string) $deliveredOrder->delivered_time, 0, 5))->toBe('16:45');
    });
});
