<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminForCatalogs(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::query()->where('slug', 'admin')->firstOrFail();
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

test('it creates a device brand from repair forms endpoint', function () {
    $admin = adminForCatalogs();

    $this->actingAs($admin)
        ->postJson(route('device-brands.store'), ['name' => 'Marca QA'])
        ->assertCreated()
        ->assertJsonPath('name', 'Marca QA');

    $this->assertDatabaseHas('device_brands', ['name' => 'Marca QA']);
});

test('it creates a repair service from repair forms endpoint', function () {
    $admin = adminForCatalogs();

    $this->actingAs($admin)
        ->postJson(route('repair-services.store'), [
            'name' => 'Servicio QA',
            'description' => 'Prueba automatizada',
            'price' => 125.50,
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'Servicio QA');

    $this->assertDatabaseHas('repair_services', ['name' => 'Servicio QA']);
});

test('it creates an inventory category from inventory forms endpoint', function () {
    $admin = adminForCatalogs();

    $this->actingAs($admin)
        ->postJson(route('categorias.store'), ['name' => 'Categoria QA'])
        ->assertCreated()
        ->assertJsonPath('name', 'Categoria QA');

    $this->assertDatabaseHas('categories', ['name' => 'Categoria QA']);
});
