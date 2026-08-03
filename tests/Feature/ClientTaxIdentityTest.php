<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminWithModules(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);

    return $user;
}

test('it creates a natural person client with cedula and blocks duplicates', function () {
    $admin = adminWithModules();

    $payload = [
        'name' => 'Juan Perez',
        'client_type' => 'natural',
        'cedula' => '001-123456-0001A',
        'phone' => '88880001',
        'status' => 'active',
    ];

    $this->actingAs($admin)->post(route('clientes.store'), $payload)
        ->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'name' => 'Juan Perez',
        'client_type' => 'natural',
        'cedula' => '001-123456-0001A',
    ]);

    $this->actingAs($admin)->post(route('clientes.store'), $payload)
        ->assertSessionHasErrors('cedula');
});

test('it creates a company client with ruc and blocks duplicates', function () {
    $admin = adminWithModules();

    $payload = [
        'name' => 'Ferreteria Central',
        'client_type' => 'company',
        'business_name' => 'Ferreteria Central S.A.',
        'ruc' => 'J0310000000012',
        'phone' => '88880002',
        'status' => 'active',
    ];

    $this->actingAs($admin)->post(route('clientes.store'), $payload)
        ->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'name' => 'Ferreteria Central',
        'client_type' => 'company',
        'ruc' => 'J0310000000012',
    ]);

    $this->actingAs($admin)->post(route('clientes.store'), $payload)
        ->assertSessionHasErrors('ruc');
});

test('credit statement shows cedula for natural person clients', function () {
    $admin = adminWithModules();

    $client = Client::create([
        'name' => 'Maria Lopez',
        'client_type' => 'natural',
        'cedula' => '001-123456-0002B',
        'phone' => '88880003',
        'credit_enabled' => true,
        'credit_limit' => 1000,
        'credit_days' => 30,
        'status' => 'active',
    ]);

    Sale::create([
        'invoice_number' => 'FAC-0001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'billing_document_type' => 'cedula',
        'billing_ruc' => $client->cedula,
        'date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
        'payment_type' => 'credit',
        'tax_included' => false,
        'tax_rate' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('creditos.statement', $client->id))
        ->assertOk()
        ->assertSee('Cédula')
        ->assertSee('001-123456-0002B');
});
