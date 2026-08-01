<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'payment_type' => 'cash',
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_orders', [
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
        ]);
    });
});
