<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function proformaQuickClientAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);

    return $user;
}

test('nueva proforma shows quick client option', function () {
    $admin = proformaQuickClientAdmin();

    $this->actingAs($admin)
        ->get(route('proformas.pos'))
        ->assertOk()
        ->assertSee('Cliente Rápido')
        ->assertSee(route('clientes.quick-store'), false);
});

test('quick client store from proforma creates client via ajax', function () {
    $admin = proformaQuickClientAdmin();

    $this->actingAs($admin)
        ->postJson(route('clientes.quick-store'), [
            'name' => 'Cliente Proforma Rapido',
            'phone' => '88881122',
            'client_type' => 'natural',
            'address' => 'Estelí',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('client.name', 'Cliente Proforma Rapido');

    $this->assertDatabaseHas('clients', [
        'name' => 'Cliente Proforma Rapido',
        'phone' => '88881122',
        'client_type' => 'natural',
    ]);

    expect(Client::query()->where('name', 'Cliente Proforma Rapido')->exists())->toBeTrue();
});
