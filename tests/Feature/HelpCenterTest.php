<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('help center requires authentication', function () {
    $this->get(route('help.index'))->assertRedirect(route('login'));
});

test('every authenticated user can browse offline help and common solutions', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)->get(route('help.index'))
        ->assertOk()
        ->assertSee('Centro de ayuda')
        ->assertSee('Ayuda sin Internet')
        ->assertSee('No puedo realizar una venta')
        ->assertSee('El ticket no imprime')
        ->assertSee('Otro equipo no puede entrar al sistema')
        ->assertSee('data-help-article', false)
        ->assertSee('data-help-category', false)
        ->assertSee('help-search');
});

test('help center link is visible in the sidebar without configuration permissions', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)->get(route('help.index'))
        ->assertOk()
        ->assertSee('Centro de ayuda');
});
