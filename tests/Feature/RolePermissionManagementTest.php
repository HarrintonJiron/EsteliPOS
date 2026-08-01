<?php

use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rbacPermission(string $slug): Permission
{
    [$module, $action] = explode('.', $slug, 2);
    return Permission::firstOrCreate(['slug' => $slug], [
        'name' => ucfirst(str_replace('_', ' ', $action)), 'module' => $module, 'action' => $action,
    ]);
}

function rbacAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->attach($role);
    return $admin;
}

test('rbac permissions protect configuration areas independently', function () {
    $user = User::factory()->create(['is_active' => true]);
    $role = Role::create(['name' => 'Gestor de configuración', 'slug' => 'gestor_configuracion']);
    $user->roles()->attach($role);
    Module::where('slug', 'configuracion')->firstOrFail()->roles()->attach($role);
    $user->directPermissions()->sync([
        rbacPermission('configuracion.view')->id,
        rbacPermission('configuracion.manage_roles')->id,
    ]);

    $this->actingAs($user)->get(route('settings.roles'))->assertOk();
    $this->actingAs($user)->get(route('settings.users'))->assertForbidden();
    $this->actingAs($user)->get(route('settings.permissions'))->assertForbidden();
});

test('system role identity is protected while its permission matrix can change', function () {
    $admin = rbacAdmin();
    $role = Role::create(['name' => 'Cajero', 'slug' => 'cajero', 'description' => 'Original', 'is_system' => true]);
    $permission = rbacPermission('caja.open');

    $this->actingAs($admin)->patch(route('settings.roles.update', $role), [
        'name' => 'Nombre alterado', 'slug' => 'slug-alterado', 'description' => 'Actualizado',
        'permissions' => [$permission->id],
    ])->assertSessionHasErrors(['name', 'slug']);

    $this->actingAs($admin)->patch(route('settings.roles.update', $role), [
        'name' => 'Cajero', 'slug' => 'cajero', 'description' => 'Actualizado',
        'permissions' => [$permission->id],
    ])->assertRedirect(route('settings.roles.show', $role));

    expect($role->fresh()->name)->toBe('Cajero')
        ->and($role->fresh()->slug)->toBe('cajero')
        ->and($role->fresh()->description)->toBe('Actualizado')
        ->and($role->fresh()->hasPermission('caja.open'))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['action' => 'role.updated', 'model_id' => $role->id]);
});

test('administrator role always retains every permission', function () {
    $admin = rbacAdmin();
    $role = Role::where('slug', 'admin')->firstOrFail();
    $one = rbacPermission('configuracion.view');
    $two = rbacPermission('configuracion.manage_roles');

    $this->actingAs($admin)->patch(route('settings.roles.update', $role), [
        'name' => $role->name, 'slug' => $role->slug, 'description' => $role->description,
        'permissions' => [$one->id],
    ])->assertRedirect();

    expect($role->fresh()->permissions->pluck('id'))->toContain($one->id, $two->id);
});

test('roles can be cloned with their complete permission set', function () {
    $admin = rbacAdmin();
    $source = Role::create(['name' => 'Bodega', 'slug' => 'bodega']);
    $source->permissions()->sync([rbacPermission('inventario.view')->id, rbacPermission('inventario.adjust')->id]);

    $this->actingAs($admin)->post(route('settings.roles.clone', $source), [
        'name' => 'Bodega temporal', 'slug' => 'bodega_temporal', 'description' => 'Copia controlada',
    ])->assertRedirect();

    $clone = Role::where('slug', 'bodega_temporal')->firstOrFail();
    expect($clone->permissions->pluck('slug')->sort()->values()->all())
        ->toBe($source->permissions->pluck('slug')->sort()->values()->all());
    $this->assertDatabaseHas('audit_logs', ['action' => 'role.cloned', 'model_id' => $clone->id]);
});

test('deleting a used custom role requires and applies reassignment', function () {
    $admin = rbacAdmin();
    $old = Role::create(['name' => 'Temporal', 'slug' => 'temporal']);
    $replacement = Role::create(['name' => 'Vendedor', 'slug' => 'vendedor']);
    $user = User::factory()->create(['is_active' => true]);
    $user->roles()->attach($old);

    $this->actingAs($admin)->delete(route('settings.roles.destroy', $old))->assertSessionHas('error');
    $this->assertDatabaseHas('roles', ['id' => $old->id]);

    $this->actingAs($admin)->delete(route('settings.roles.destroy', $old), ['replacement_role_id' => $replacement->id])
        ->assertRedirect(route('settings.roles'));

    $this->assertDatabaseMissing('roles', ['id' => $old->id]);
    expect($user->fresh()->hasRole('vendedor'))->toBeTrue();
});

test('global matrix and role comparison render effective access', function () {
    $admin = rbacAdmin();
    $first = Role::create(['name' => 'Primero', 'slug' => 'primero']);
    $second = Role::create(['name' => 'Segundo', 'slug' => 'segundo']);
    $permission = rbacPermission('ventas.view');
    $first->permissions()->attach($permission);

    $this->actingAs($admin)->get(route('settings.permissions'))->assertOk()->assertSee('Matriz global de permisos')->assertSee('ventas.view');
    $this->actingAs($admin)->get(route('settings.roles.compare', ['roles' => [$first->id, $second->id]]))
        ->assertOk()->assertSee('Comparar roles')->assertSee('Primero')->assertSee('Segundo');
    expect(Permission::where('slug', 'contabilidad.view')->exists())->toBeTrue()
        ->and(Permission::where('slug', 'reparaciones.edit')->exists())->toBeTrue();
});
