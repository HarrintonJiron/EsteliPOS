<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('legacy serialized settings are read using their declared type', function () {
    Setting::create([
        'key' => 'tax_rate',
        'value' => '"15"',
        'type' => 'float',
        'group' => 'general',
    ]);

    expect(Setting::get('tax_rate'))->toBe(15.0);
});

test('setting writes preserve boolean and numeric types without json quoting', function () {
    Setting::set('two_factor_enabled', false, null, 'security');
    Setting::set('tax_rate', 15, null, 'general');

    $booleanSetting = Setting::where('key', 'two_factor_enabled')->firstOrFail();
    $taxSetting = Setting::where('key', 'tax_rate')->firstOrFail();

    expect($booleanSetting->value)->toBe('0')
        ->and($booleanSetting->type)->toBe('boolean')
        ->and(Setting::get('two_factor_enabled'))->toBeFalse()
        ->and($taxSetting->type)->toBe('float')
        ->and(Setting::get('tax_rate'))->toBe(15.0);
});

test('pivot administrators can access settings during the legacy transition', function () {
    $role = Role::create([
        'name' => 'Administrador de prueba',
        'slug' => 'admin',
        'is_system' => true,
    ]);
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $user->roles()->attach($role);

    $this->actingAs($user)->get(route('settings.index'))->assertOk();
});

test('inactive administrators cannot access settings or log in', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'is_active' => false,
        'password' => Hash::make('secret-password'),
    ]);

    $this->actingAs($user)->get(route('settings.index'))->assertRedirect(route('login'));

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('module updates invalidate both module caches', function () {
    $module = Module::updateOrCreate(['slug' => 'ventas'], [
        'name' => 'Ventas',
        'is_active' => true,
    ]);

    expect(Module::isActive('ventas'))->toBeTrue();
    Module::getActiveModules();

    $module->update(['is_active' => false]);

    expect(Cache::has('modules.ventas.active'))->toBeFalse()
        ->and(Cache::has('modules.active'))->toBeFalse()
        ->and(Module::isActive('ventas'))->toBeFalse();
});

test('unchecked security options are persisted as false', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->attach(Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]));

    $this->actingAs($admin)->post(route('settings.security'), [
        'session_timeout' => 60,
        'max_login_attempts' => 5,
        'password_min_length' => 8,
    ])->assertRedirect(route('settings.security'));

    expect(Setting::get('password_require_uppercase'))->toBeFalse()
        ->and(Setting::get('password_require_lowercase'))->toBeFalse()
        ->and(Setting::get('password_require_numbers'))->toBeFalse()
        ->and(Setting::get('password_require_special_chars'))->toBeFalse()
        ->and(Setting::get('two_factor_enabled'))->toBeFalse();
});

test('settings dashboard exposes searchable sections and honest availability states', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->attach(Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]));

    $response = $this->actingAs($admin)->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Buscar usuarios, impuestos, respaldos')
        ->assertSee('Caja y facturación')
        ->assertSee('Diagnóstico del sistema')
        ->assertSee('Próximamente')
        ->assertSee('Versión 2.0')
        ->assertSee('data-settings-card', false)
        ->assertSee('data-category-filter', false);

    $sections = collect($response->viewData('sections'))->keyBy('id');

    foreach (['general', 'company', 'users', 'roles', 'permissions', 'modules', 'appearance', 'taxes'] as $id) {
        expect($sections[$id]['status'])->toBe('Disponible')
            ->and($sections[$id]['url'])->not->toBeNull();
    }

    foreach (['sequences', 'security', 'sales', 'inventory', 'backups', 'audit', 'diagnostics'] as $id) {
        expect($sections[$id]['status'])->toBe('Próximamente')
            ->and($sections[$id]['metric'])->toBe('Versión 2.0')
            ->and($sections[$id]['url'])->toBeNull();
    }
});
