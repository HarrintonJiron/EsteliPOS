<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function posHeldAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('the pos explains F4 and F6 as leaving an invoice on hold and resuming it', function () {
    $html = test()->actingAs(posHeldAdmin())->get(route('facturacion.pos'))->assertOk()->getContent();

    expect($html)
        ->toContain('Dejar en espera · F4')
        ->toContain('id="heldTicketsBtn"')
        ->toContain('Facturas en espera')
        ->toContain('<b>F4</b> Dejar en espera')
        ->toContain('<b>F6</b> Facturas en espera')
        ->toContain('retomar una para cobrarla')
        ->not->toContain('Suspender')
        ->not->toContain('Tickets Apartados')
        ->not->toContain('<b>F4</b> Apartar');
});

test('the held invoices list speaks of resuming and discarding, not recovering tickets', function () {
    $html = test()->actingAs(posHeldAdmin())->get(route('facturacion.pos'))->assertOk()->getContent();

    expect($html)
        ->toContain('Retomar')
        ->toContain('Descartar')
        ->toContain('No hay facturas en espera')
        ->toContain('Ya tienes una factura en curso')
        ->not->toContain('No hay tickets apartados')
        ->not->toContain('¿Reemplazar ticket actual?');
});
