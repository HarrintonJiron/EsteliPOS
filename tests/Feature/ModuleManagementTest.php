<?php

use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function moduleAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

function modulePayload(?callable $change = null): array
{
    return Module::with('roles')->get()->mapWithKeys(function (Module $module) use ($change) {
        $row = [
            'is_active' => $module->is_active ? '1' : '0',
            'sort_order' => $module->sort_order,
            'roles' => $module->roles->pluck('id')->all(),
        ];

        return [$module->id => $change ? $change($module, $row) : $row];
    })->all();
}

test('the complete module catalog and its dependencies are installed', function () {
    expect(Module::count())->toBe(13)
        ->and(Module::where('is_active', true)->count())->toBe(13)
        ->and(Module::where('slug', 'configuracion')->firstOrFail()->is_core)->toBeTrue()
        ->and(Module::where('slug', 'ventas')->firstOrFail()->dependencies)->toBe(['inventario', 'clientes'])
        ->and(Module::where('slug', 'contabilidad')->firstOrFail()->dependencies)->toBe(['ventas', 'compras']);
});

test('inactive modules are hidden from navigation and their routes return not found', function () {
    $admin = moduleAdmin();
    Module::where('slug', 'ventas')->firstOrFail()->update(['is_active' => false]);

    $this->actingAs($admin)->get(route('dashboard.general'))
        ->assertOk()
        ->assertDontSee('Facturación/POS');
    $this->actingAs($admin)->get(route('facturacion.index'))->assertNotFound();
});

test('a non administrator needs an assigned role to access an active module', function () {
    $user = User::factory()->create(['is_active' => true]);
    $role = Role::create(['name' => 'Inventarista', 'slug' => 'inventarista']);
    $user->roles()->attach($role);
    $module = Module::where('slug', 'inventario')->firstOrFail();

    expect(app(ModuleAccessService::class)->canAccessSlug('inventario', $user))->toBeFalse();
    $this->actingAs($user)->get(route('inventario.index'))->assertForbidden();

    $module->roles()->attach($role);
    Module::flushModuleCache();
    expect(app(ModuleAccessService::class)->canAccessSlug('inventario', $user))->toBeTrue();
});

test('the general dashboard only queries and renders authorized module widgets', function () {
    $user = User::factory()->create(['is_active' => true]);
    $role = Role::create(['name' => 'Solo inventario', 'slug' => 'solo_inventario']);
    $user->roles()->attach($role);
    Module::where('slug', 'inventario')->firstOrFail()->roles()->attach($role);
    Module::flushModuleCache();

    $this->actingAs($user)->get(route('dashboard.general'))
        ->assertOk()
        ->assertSee('Valor inventario')
        ->assertDontSee('Ventas Hoy')
        ->assertDontSee('Clientes Activos')
        ->assertDontSee('Facturas Pendientes');
});

test('a dependency cannot be disabled while an active module requires it', function () {
    $admin = moduleAdmin();
    $inventario = Module::where('slug', 'inventario')->firstOrFail();
    $payload = modulePayload(fn (Module $module, array $row) => $module->is($inventario)
        ? array_merge($row, ['is_active' => '0'])
        : $row);

    $this->actingAs($admin)->put(route('settings.modules.update'), ['modules' => $payload])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($inventario->fresh()->is_active)->toBeTrue();
});

test('the configuration core module cannot be disabled', function () {
    $admin = moduleAdmin();
    $configuration = Module::where('slug', 'configuracion')->firstOrFail();
    $payload = modulePayload(fn (Module $module, array $row) => $module->is($configuration)
        ? array_merge($row, ['is_active' => '0'])
        : $row);

    $this->actingAs($admin)->put(route('settings.modules.update'), ['modules' => $payload])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($configuration->fresh()->is_active)->toBeTrue();
});

test('a safe deactivation is timestamped audited and immediately enforced', function () {
    $admin = moduleAdmin();
    $payroll = Module::where('slug', 'planilla')->firstOrFail();
    $payload = modulePayload(fn (Module $module, array $row) => $module->is($payroll)
        ? array_merge($row, ['is_active' => '0'])
        : $row);

    $this->actingAs($admin)->put(route('settings.modules.update'), ['modules' => $payload])
        ->assertRedirect(route('settings.modules'))
        ->assertSessionHas('success');

    $payroll->refresh();
    expect($payroll->is_active)->toBeFalse()
        ->and($payroll->deactivated_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'modules.updated')->exists())->toBeTrue();
    $this->actingAs($admin)->get(route('planilla.index'))->assertNotFound();
});
