<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Services\CreditOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
});

function creditOverrideAdmin(string $email, string $password = 'AdminPassword123!'): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $user = User::factory()->create([
        'role' => 'admin',
        'email' => $email,
        'username' => str($email)->before('@')->toString(),
        'password' => $password,
        'is_active' => true,
    ]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('credit override token is bound to cashier client amount and can only be consumed once', function () {
    $cashier = creditOverrideAdmin('cashier@example.test');
    $administrator = creditOverrideAdmin('supervisor@example.test');
    $client = Client::query()->create([
        'name' => 'Cliente autorizado',
        'credit_enabled' => true,
        'credit_limit' => 100,
    ]);
    $service = app(CreditOverrideService::class);

    $token = $service->issue($cashier, $administrator, $client, 150);

    expect($token)->toHaveLength(64)
        ->and($service->consume($token, $cashier, $client, 150))->toBe($administrator->id)
        ->and($service->consume($token, $cashier, $client, 150))->toBeNull()
        ->and(AuditLog::query()->where('action', 'credit.override.authorized')->exists())->toBeTrue();
});

test('administrator credentials issue a temporary override without exposing the password', function () {
    $cashier = creditOverrideAdmin('caja@example.test');
    $administrator = creditOverrideAdmin('admin@example.test');
    $client = Client::query()->create([
        'name' => 'Cliente sin cupo',
        'credit_enabled' => true,
        'credit_limit' => 100,
    ]);

    $this->actingAs($cashier)->postJson(route('facturacion.credit-override'), [
        'admin_login' => $administrator->email,
        'password' => 'incorrecta',
        'client_id' => $client->id,
        'amount' => 150,
    ])->assertUnprocessable()
        ->assertJsonMissing(['password' => 'incorrecta']);

    $response = $this->actingAs($cashier)->postJson(route('facturacion.credit-override'), [
        'admin_login' => $administrator->username,
        'password' => 'AdminPassword123!',
        'client_id' => $client->id,
        'amount' => 150,
    ])->assertOk()
        ->assertJsonPath('administrator', $administrator->name)
        ->assertJsonPath('expires_in_minutes', 5);

    expect($response->json('token'))->toHaveLength(64)
        ->and(json_encode(AuditLog::query()->latest()->firstOrFail()->toArray()))->not->toContain('AdminPassword123!');
});

test('client list shows the credit still available to the cashier', function () {
    $user = creditOverrideAdmin('viewer@example.test');
    Client::query()->create([
        'name' => 'Cliente disponible',
        'credit_enabled' => true,
        'credit_limit' => 750,
    ]);

    $this->actingAs($user)->get(route('clientes.index'))
        ->assertOk()
        ->assertSee('Disponible')
        ->assertSee('C$ 750.00');
});
