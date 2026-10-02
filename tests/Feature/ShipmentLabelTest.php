<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function shipmentLabelAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

test('the shipment label shows recipient data and a fragile notice, without any weight field', function () {
    $admin = shipmentLabelAdmin();
    $client = Client::query()->create(['name' => 'Cliente etiqueta', 'phone' => '85149467', 'status' => 'active']);

    $shipment = Shipment::create([
        'number' => 'ENV-LABEL-001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'recipient_name' => 'Cell Connection',
        'recipient_phone' => '85149467',
        'department' => 'Estelí',
        'municipality' => 'Estelí',
        'address' => 'Barrio Central',
        'is_fragile' => true,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->get(route('envios.label', $shipment));

    $response->assertOk()
        ->assertSee('Cell Connection')
        ->assertSee('Estelí')
        ->assertSee('85149467')
        ->assertSee('FRÁGIL')
        ->assertDontSee('peso', false)
        ->assertDontSee('Peso');
});

test('the shipment label hides the fragile notice when the package is not marked fragile', function () {
    $admin = shipmentLabelAdmin();

    $shipment = Shipment::create([
        'number' => 'ENV-LABEL-002',
        'user_id' => $admin->id,
        'recipient_name' => 'Cliente sin fragil',
        'department' => 'Managua',
        'address' => 'Reparto Test',
        'is_fragile' => false,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)->get(route('envios.label', $shipment))
        ->assertOk()
        ->assertDontSee('FRÁGIL');
});

test('the shipment show and index pages link to the printable label', function () {
    $admin = shipmentLabelAdmin();

    $shipment = Shipment::create([
        'number' => 'ENV-LABEL-003',
        'user_id' => $admin->id,
        'recipient_name' => 'Cliente enlace',
        'department' => 'León',
        'address' => 'Direccion cualquiera',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)->get(route('envios.show', $shipment))
        ->assertOk()
        ->assertSee(route('envios.label', $shipment), false);

    $this->actingAs($admin)->get(route('envios.index'))
        ->assertOk()
        ->assertSee(route('envios.label', $shipment), false);
});
