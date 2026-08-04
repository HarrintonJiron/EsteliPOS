<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function quickClientAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);

    return $user;
}

test('quick client form allows creating natural client without cedula', function () {
    $admin = quickClientAdmin();

    $this->actingAs($admin)
        ->post(route('clientes.store'), [
            'quick_client_form' => 1,
            'name' => 'Cliente Rapido Uno',
            'client_type' => 'natural',
            'phone' => '88880011',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'name' => 'Cliente Rapido Uno',
        'client_type' => 'natural',
        'phone' => '88880011',
        'cedula' => null,
    ]);
});

test('quick client form requires phone', function () {
    $admin = quickClientAdmin();

    $this->actingAs($admin)
        ->post(route('clientes.store'), [
            'quick_client_form' => 1,
            'name' => 'Cliente Sin Telefono',
            'client_type' => 'natural',
        ])
        ->assertSessionHasErrors('phone');
});

test('quick client form validates cedula format only when checkbox is selected and value is provided', function () {
    $admin = quickClientAdmin();

    $this->actingAs($admin)
        ->post(route('clientes.store'), [
            'quick_client_form' => 1,
            'name' => 'Cliente Cedula Invalida',
            'client_type' => 'natural',
            'phone' => '88880012',
            'save_cedula_identity' => 1,
            'cedula' => 'CEDULA-INVALIDA',
        ])
        ->assertSessionHasErrors('cedula');
});

test('quick client form ignores cedula when checkbox is not selected', function () {
    $admin = quickClientAdmin();

    $this->actingAs($admin)
        ->post(route('clientes.store'), [
            'quick_client_form' => 1,
            'name' => 'Cliente Cedula Ignorada',
            'client_type' => 'natural',
            'phone' => '88880013',
            'cedula' => '001-123456-0003C',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'name' => 'Cliente Cedula Ignorada',
        'cedula' => null,
    ]);
});

test('quick client form prevents duplicate cedula when checkbox is selected', function () {
    $admin = quickClientAdmin();

    Client::create([
        'name' => 'Cliente Base',
        'client_type' => 'natural',
        'cedula' => '001-123456-0099Z',
        'phone' => '88880014',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->post(route('clientes.store'), [
            'quick_client_form' => 1,
            'name' => 'Cliente Duplicado',
            'client_type' => 'natural',
            'phone' => '88880015',
            'save_cedula_identity' => 1,
            'cedula' => '001-123456-0099Z',
        ])
        ->assertSessionHasErrors('cedula');
});

test('full client form behavior remains unchanged for natural clients requiring cedula', function () {
    $admin = quickClientAdmin();

    $this->actingAs($admin)
        ->post(route('clientes.store'), [
            'name' => 'Cliente Completo Sin Cedula',
            'client_type' => 'natural',
            'phone' => '88880016',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('cedula');
});
