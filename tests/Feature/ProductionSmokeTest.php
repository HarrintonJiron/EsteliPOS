<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function smokeAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('guest is redirected to login from protected routes', function () {
    $this->get(route('dashboard.general'))->assertRedirect(route('login'));
    $this->get(route('facturacion.pos'))->assertRedirect(route('login'));
});

test('login page renders without server errors', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Entrar a mi negocio');
});

test('administrator can reach core operational screens', function (string $routeName) {
    $this->actingAs(smokeAdmin())
        ->get(route($routeName))
        ->assertOk();
})->with([
    'dashboard' => ['dashboard.general'],
    'pos' => ['facturacion.pos'],
    'facturacion list' => ['facturacion.index'],
    'inventario' => ['inventario.index'],
    'compras' => ['compras.index'],
    'clientes' => ['clientes.index'],
    'proformas' => ['proformas.index'],
    'reparaciones' => ['reparaciones.index'],
    'planilla' => ['planilla.index'],
    'nomina' => ['nomina.index'],
    'reportes' => ['reportes.index'],
    'settings' => ['settings.index'],
]);

test('administrator can reach payroll submodule screens', function (string $routeName, int $expectedStatus = 200) {
    $response = $this->actingAs(smokeAdmin())->get(route($routeName));

    $response->assertStatus($expectedStatus);
})->with([
    'employees redirect' => ['employees.index', 302],
    'leave' => ['leave.index', 200],
    'loans' => ['loans.index', 200],
    'bonuses' => ['bonuses.index', 200],
    'deductions' => ['deductions.index', 200],
]);

test('administrator can reach exchange rate settings', function () {
    $this->actingAs(smokeAdmin())
        ->get(route('settings.exchange-rates.index'))
        ->assertOk();
});

test('new production tables exist after migrations', function () {
    expect(Schema::hasTable('exchange_rates'))->toBeTrue()
        ->and(Schema::hasTable('leave_requests'))->toBeTrue()
        ->and(Schema::hasTable('loans'))->toBeTrue()
        ->and(Schema::hasTable('bonuses'))->toBeTrue()
        ->and(Schema::hasTable('deductions'))->toBeTrue()
        ->and(Schema::hasColumn('sales', 'amount_paid'))->toBeTrue()
        ->and(Schema::hasColumn('sales', 'change_amount'))->toBeTrue()
        ->and(Schema::hasColumn('repair_orders', 'warranty_enabled'))->toBeTrue();
});
