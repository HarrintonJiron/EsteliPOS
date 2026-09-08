<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function adminWithRole(): array
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    return [$admin, $role];
}

test('an administrator can create a user with profile fields roles and direct permissions', function () {
    [$admin] = adminWithRole();
    $role = Role::create(['name' => 'Vendedor', 'slug' => 'seller']);
    $permission = Permission::create(['name' => 'Ver ventas', 'slug' => 'sales.view', 'module' => 'sales', 'action' => 'view']);

    $this->actingAs($admin)->post(route('settings.users.store'), [
        'name' => 'María López',
        'username' => 'maria.lopez',
        'email' => 'maria@example.com',
        'phone' => '+505 8888 9999',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'roles' => [$role->id],
        'permissions' => [$permission->id],
        'is_active' => '1',
        'force_password_change' => '1',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $user = User::where('username', 'maria.lopez')->firstOrFail();
    expect($user->phone)->toBe('+505 8888 9999')
        ->and($user->force_password_change)->toBeTrue()
        ->and($user->hasRole('seller'))->toBeTrue()
        ->and($user->hasPermission('sales.view'))->toBeTrue()
        ->and(Hash::check('Password123!', $user->password))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'model_id' => $user->id]);
});

test('users can be searched and filtered by status and role', function () {
    [$admin] = adminWithRole();
    $seller = Role::create(['name' => 'Vendedor', 'slug' => 'seller']);
    $match = User::factory()->create(['name' => 'Usuario Especial', 'username' => 'especial', 'is_active' => false]);
    $match->roles()->attach($seller);
    User::factory()->create(['name' => 'Otro Usuario', 'is_active' => true]);

    $this->actingAs($admin)->get(route('settings.users', ['search' => 'Especial', 'status' => 'inactive', 'role' => 'seller']))
        ->assertOk()->assertSee('Usuario Especial')->assertDontSee('Otro Usuario');
});

test('the last active administrator cannot be deactivated demoted or deleted', function () {
    [$admin, $adminRole] = adminWithRole();

    $this->actingAs($admin)->post(route('settings.users.toggle-active', $admin))->assertSessionHas('error');
    $this->actingAs($admin)->patch(route('settings.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'roles' => [],
    ])->assertSessionHas('error');
    $this->actingAs($admin)->delete(route('settings.users.destroy', $admin))->assertSessionHas('error');

    expect($admin->fresh()->is_active)->toBeTrue()->and($admin->fresh()->roles->contains($adminRole))->toBeTrue();
});

test('password reset never exposes plaintext and can force a change', function () {
    [$admin] = adminWithRole();
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)->post(route('settings.users.reset-password', $user), [
        'password' => 'Temporary123!',
        'password_confirmation' => 'Temporary123!',
        'force_password_change' => '1',
    ]);

    $response->assertRedirect(route('settings.users.show', $user))->assertDontSee('Temporary123!');
    expect(Hash::check('Temporary123!', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->force_password_change)->toBeTrue();
    $log = AuditLog::where('action', 'user.password_reset')->latest()->firstOrFail();
    expect(json_encode($log->toArray()))->not->toContain('Temporary123!');
});

test('a forced user must change the temporary password before using the system', function () {
    $user = User::factory()->create([
        'username' => 'forced.user',
        'password' => 'Temporary123!',
        'is_active' => true,
        'force_password_change' => true,
    ]);

    $this->actingAs($user)->get(route('facturacion.pos'))->assertRedirect(route('password.change'));
    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'Temporary123!',
        'password' => 'Permanent123!',
        'password_confirmation' => 'Permanent123!',
    ])->assertRedirect(route('access.unavailable'));

    expect($user->fresh()->force_password_change)->toBeFalse()
        ->and(Hash::check('Permanent123!', $user->fresh()->password))->toBeTrue();
});

test('an active user can sign in with username', function () {
    $user = User::factory()->create(['username' => 'cajero01', 'password' => 'Password123!', 'is_active' => true]);

    $this->post(route('login'), ['login' => 'cajero01', 'password' => 'Password123!'])
        ->assertRedirect(route('access.unavailable'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('login screen lists active users for quick selection', function () {
    User::factory()->create(['name' => 'Cajero Rápido', 'username' => 'cajero.rapido', 'is_active' => true]);
    User::factory()->create(['name' => 'Usuario Inactivo', 'is_active' => false]);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Cajero Rápido')
        ->assertDontSee('Usuario Inactivo');
});

test('an active user can sign in with a configured pin', function () {
    $user = User::factory()->create([
        'username' => 'cajero.pin',
        'pin_hash' => Hash::make('2580'),
        'is_active' => true,
    ]);

    $this->post(route('login'), [
        'user_id' => $user->id,
        'auth_method' => 'pin',
        'pin' => '2580',
    ])->assertRedirect(route('access.unavailable'));

    $this->assertAuthenticatedAs($user);
});

test('an invalid pin does not authenticate the selected user', function () {
    $user = User::factory()->create([
        'pin_hash' => Hash::make('2580'),
        'is_active' => true,
    ]);

    $this->post(route('login'), [
        'user_id' => $user->id,
        'auth_method' => 'pin',
        'pin' => '1111',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('an authenticated user can switch quickly to an active user with pin', function () {
    $current = User::factory()->create(['name' => 'Administrador actual', 'is_active' => true]);
    $target = User::factory()->create([
        'name' => 'Cajero de turno',
        'pin_hash' => Hash::make('2580'),
        'is_active' => true,
    ]);

    $this->actingAs($current)->post(route('auth.switch-user'), [
        'user_id' => $target->id,
        'pin' => '2580',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($target);
    expect(AuditLog::query()
        ->where('user_id', $current->id)
        ->where('action', 'user.quick_switch')
        ->where('model_id', $target->id)
        ->exists())->toBeTrue();
});

test('an invalid quick switch pin keeps the current session', function () {
    $current = User::factory()->create(['is_active' => true]);
    $target = User::factory()->create([
        'pin_hash' => Hash::make('2580'),
        'is_active' => true,
    ]);

    $this->actingAs($current)->from(route('home'))->post(route('auth.switch-user'), [
        'user_id' => $target->id,
        'pin' => '1111',
    ])->assertRedirect(route('home'))->assertSessionHasErrors('switch_user');

    $this->assertAuthenticatedAs($current);
});
