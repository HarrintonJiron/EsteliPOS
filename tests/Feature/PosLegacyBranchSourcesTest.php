<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('the pos remains available before the optional branch source migration is applied', function () {
    $role = Role::query()->create([
        'name' => 'Administrador',
        'slug' => 'admin',
        'is_system' => true,
    ]);
    $user = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);
    $user->roles()->attach($role);
    openCashSessionFor($user);

    Schema::drop('branch_product_sources');

    $this->actingAs($user)
        ->get(route('facturacion.pos'))
        ->assertOk()
        ->assertSee('id="selectedItemQty"', false)
        ->assertSee('Teclado físico o digital');
});
