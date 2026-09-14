<?php

use App\Models\Category;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function limitedNavigationUser(array $permissions = []): User
{
    $user = User::factory()->create(['is_active' => true]);
    $role = Role::create([
        'name' => 'Rol limitado '.uniqid(),
        'slug' => 'limitado-'.uniqid(),
        'is_system' => false,
    ]);
    $user->roles()->attach($role);

    foreach ($permissions as $slug) {
        $permission = Permission::firstOrCreate(['slug' => $slug], [
            'name' => $slug,
            'module' => str($slug)->before('.')->toString(),
            'action' => str($slug)->after('.')->toString(),
        ]);
        $role->permissions()->attach($permission);

        $module = Module::query()->where('slug', $permission->module)->first();
        if ($module) {
            $module->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    return $user;
}

test('the root sends a limited user to the first module they can access', function () {
    $user = limitedNavigationUser(['inventario.view']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('inventario.index'));
});

test('a user without modules sees a friendly limited access page', function () {
    $user = limitedNavigationUser();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('access.unavailable'));

    $this->actingAs($user)
        ->get(route('access.unavailable'))
        ->assertOk()
        ->assertSee('Tu cuenta tiene acceso limitado');
});

test('a forbidden web page redirects safely and shows an alert', function () {
    $user = limitedNavigationUser(['inventario.view']);

    $this->actingAs($user)
        ->get(route('dashboard.general'))
        ->assertRedirect(route('inventario.index'))
        ->assertSessionHas('error');
});

test('a forbidden json request remains forbidden', function () {
    $user = limitedNavigationUser(['inventario.view']);

    $this->actingAs($user)
        ->getJson(route('dashboard.general'))
        ->assertForbidden();
});

test('a limited user cannot execute a hidden mutation by direct url', function () {
    $user = limitedNavigationUser(['inventario.view']);

    $this->actingAs($user)
        ->post(route('categorias.store'), ['name' => 'Categoría no autorizada'])
        ->assertRedirect(route('inventario.index'))
        ->assertSessionHas('error');

    expect(Category::query()->where('name', 'Categoría no autorizada')->exists())->toBeFalse();
});

test('every authenticated business mutation declares an action permission', function () {
    $exceptions = ['logout', 'password.update', 'auth.switch-user'];

    $unprotected = collect(Route::getRoutes())->filter(function ($route) use ($exceptions): bool {
        $methods = $route->methods();
        $mutates = count(array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE'])) > 0;
        $authenticated = in_array('auth', $route->gatherMiddleware(), true);
        $hasPermission = collect($route->gatherMiddleware())
            ->contains(fn (string $middleware): bool => str_starts_with($middleware, 'permission:'));

        return $mutates
            && $authenticated
            && ! $hasPermission
            && ! in_array($route->getName(), $exceptions, true);
    });

    expect($unprotected->map(fn ($route) => $route->getName())->values()->all())->toBe([]);
});
