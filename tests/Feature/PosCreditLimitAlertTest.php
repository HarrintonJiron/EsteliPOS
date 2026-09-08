<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pos exposes client available credit and renders an internal over-limit warning', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);
    $client = Client::query()->create([
        'code' => 'CLI-CREDITO-ALERTA',
        'name' => 'Cliente con límite',
        'phone' => '88880000',
        'credit_enabled' => true,
        'credit_limit' => 500,
        'credit_days' => 30,
    ]);

    $response = $this->actingAs($admin)->get(route('facturacion.pos'))
        ->assertOk()
        ->assertSee('creditLimitAlert', false)
        ->assertSee('Límite de crédito sobrepasado')
        ->assertSee('updateCreditLimitAlert', false)
        ->assertSee('creditExceededAmount', false)
        ->assertSee('Solicitar autorización')
        ->assertSee('creditOverrideModal', false)
        ->assertSee('creditOverrideTokenInput', false);

    $serializedClient = $response->viewData('clients')->firstWhere('id', $client->id);
    expect((float) $serializedClient->available_credit)->toBe(500.0)
        ->and((float) $serializedClient->balance)->toBe(0.0)
        ->and($serializedClient->over_limit)->toBeFalse();
});
