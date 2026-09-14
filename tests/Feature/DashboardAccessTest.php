<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashboardPermission(): Permission
{
    return Permission::firstOrCreate(['slug' => 'dashboard.view'], [
        'name' => 'Dashboard View',
        'module' => 'dashboard',
        'action' => 'view',
    ]);
}

function dashboardAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->attach($role);

    return $admin;
}

test('users without dashboard permission cannot open the general dashboard', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)->get(route('dashboard.general'))
        ->assertRedirect(route('access.unavailable'))
        ->assertSessionHas('error');
    $this->actingAs($user)->get('/')->assertRedirect(route('access.unavailable'));
});

test('users with dashboard permission can open the general dashboard', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->directPermissions()->attach(dashboardPermission());

    $this->actingAs($user)->get(route('dashboard.general'))->assertOk();
    $this->actingAs($user)->get('/')->assertRedirect(route('dashboard.general'));
});

test('administrators can open the dashboard without an explicit permission grant', function () {
    $this->actingAs(dashboardAdmin())->get(route('dashboard.general'))->assertOk();
});

test('dashboard link is hidden from navigation when the user lacks access', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('password.change'))
        ->assertOk()
        ->assertDontSee('Dashboard');
});

test('dashboard link is visible when the user has access', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->directPermissions()->attach(dashboardPermission());

    $this->actingAs($user)
        ->get(route('dashboard.general'))
        ->assertOk()
        ->assertSee('Dashboard');
});
