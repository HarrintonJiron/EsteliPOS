<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\SystemResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function systemResetAdministrator(bool $withPermission = true): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $user = User::factory()->create([
        'role' => 'admin',
        'password' => 'Password123!',
        'is_active' => true,
    ]);
    $user->roles()->sync([$role->id]);

    if ($withPermission) {
        $permission = Permission::query()->where('slug', 'configuracion.reset_system')->firstOrFail();
        $user->directPermissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

test('system reset requires its dedicated permission even for an administrator', function () {
    $admin = systemResetAdministrator(false);
    $this->mock(SystemResetService::class)->shouldNotReceive('reset');

    $this->actingAs($admin)->post(route('settings.system-reset.store'), [
        'mode' => 'clean',
        'password' => 'Password123!',
        'confirmation' => 'REINICIAR SISTEMA',
        'acknowledge' => '1',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($admin);
});

test('system reset validates the password mode and exact confirmation phrase', function () {
    $admin = systemResetAdministrator();
    $this->mock(SystemResetService::class)->shouldNotReceive('reset');

    $this->actingAs($admin)->from(route('settings.index'))->post(route('settings.system-reset.store'), [
        'mode' => 'invalid',
        'password' => 'wrong-password',
        'confirmation' => 'reiniciar',
        'acknowledge' => '0',
    ])->assertRedirect(route('settings.index'))
        ->assertSessionHasErrors(['mode', 'password', 'confirmation', 'acknowledge']);

    $this->assertAuthenticatedAs($admin);
});

test('authorized reset delegates to the protected service and closes the session', function () {
    $admin = systemResetAdministrator();
    $this->mock(SystemResetService::class)
        ->shouldReceive('reset')
        ->once()
        ->withArgs(fn (User $user, string $mode) => $user->is($admin) && $mode === 'demo')
        ->andReturn(['backup_name' => 'antes-reinicio-20260817-120000.sqlite', 'mode' => 'demo']);

    $this->actingAs($admin)->post(route('settings.system-reset.store'), [
        'mode' => 'demo',
        'password' => 'Password123!',
        'confirmation' => 'REINICIAR SISTEMA',
        'acknowledge' => '1',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('success');

    $this->assertGuest();
});

test('a failed reset reports the recovery result and keeps the administrator signed in', function () {
    $admin = systemResetAdministrator();
    $this->mock(SystemResetService::class)
        ->shouldReceive('reset')
        ->once()
        ->andThrow(new RuntimeException('La base anterior fue restaurada.'));

    $this->actingAs($admin)->from(route('settings.index'))->post(route('settings.system-reset.store'), [
        'mode' => 'clean',
        'password' => 'Password123!',
        'confirmation' => 'REINICIAR SISTEMA',
        'acknowledge' => '1',
    ])->assertRedirect(route('settings.index'))
        ->assertSessionHas('error', 'La base anterior fue restaurada.');

    $this->assertAuthenticatedAs($admin);
});

test('authorized administrator can open the reset risk screen', function () {
    $admin = systemResetAdministrator();

    $this->actingAs($admin)->get(route('settings.system-reset.create'))
        ->assertOk()
        ->assertSee('Entregar el sistema en limpio')
        ->assertSee('Entregar en limpio')
        ->assertSee('Ambiente de demostración')
        ->assertSee('REINICIAR SISTEMA')
        ->assertSee('system-reset-submit');
});

test('reset card is only shown on settings dashboard to users with the dedicated permission', function () {
    $authorized = systemResetAdministrator();
    $unauthorized = systemResetAdministrator(false);

    $this->actingAs($authorized)->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Entregar en limpio');

    $this->actingAs($unauthorized)->get(route('settings.index'))
        ->assertOk()
        ->assertDontSee('Entregar en limpio');
});
