<?php

use App\Models\Role;
use App\Models\User;
use App\Services\BackNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function backNavAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

it('does not show back navigation on main index pages', function () {
    expect(app(BackNavigationService::class)->resolve('clientes.index'))->toBeNull();
    expect(app(BackNavigationService::class)->resolve('facturacion.pos'))->toBeNull();
});

it('resolves back navigation for create pages to their index', function () {
    expect(app(BackNavigationService::class)->resolve('compras.create'))
        ->toMatchArray([
            'label' => 'Regresar',
            'href' => route('compras.index'),
        ]);
});

it('resolves contabilidad sub reports back to dashboard', function () {
    Route::get('/contabilidad', fn () => '')->name('contabilidad.dashboard');

    expect(app(BackNavigationService::class)->resolve('contabilidad.diario.index'))
        ->toMatchArray([
            'label' => 'Regresar',
            'href' => route('contabilidad.dashboard'),
        ]);
});

it('shows back button on secondary pages', function () {
    $this->actingAs(backNavAdmin())
        ->get(route('compras.create'))
        ->assertSuccessful()
        ->assertSee('Regresar', false);
});

it('does not show duplicate back button on pos screen', function () {
    $response = $this->actingAs(backNavAdmin())
        ->get(route('facturacion.pos'))
        ->assertSuccessful();

    expect(substr_count($response->getContent(), 'ui-back-button'))->toBe(0);
});
