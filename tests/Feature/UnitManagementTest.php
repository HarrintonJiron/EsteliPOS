<?php

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function unitsAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('admin can create a new unit of measure', function () {
    $admin = unitsAdmin();

    $this->actingAs($admin)->post(route('inventario.units.store'), [
        'name' => 'Barril',
        'abbreviation' => 'bbl',
        'unit_type' => 'volume',
        'is_active' => 1,
    ])->assertRedirect(route('inventario.units.index'));

    $unit = Unit::query()->where('abbreviation', 'bbl')->firstOrFail();

    expect($unit->name)->toBe('Barril')
        ->and($unit->unit_type)->toBe('volume')
        ->and($unit->is_active)->toBeTrue();
});

test('admin can update and deactivate a unit', function () {
    $admin = unitsAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $unit = Unit::query()->where('abbreviation', 'saco')->firstOrFail();

    $this->actingAs($admin)->put(route('inventario.units.update', $unit), [
        'name' => 'Saco 40 kg',
        'abbreviation' => 'saco',
        'unit_type' => 'package',
        'is_active' => 0,
    ])->assertRedirect(route('inventario.units.index'));

    $unit->refresh();

    expect($unit->name)->toBe('Saco 40 kg')
        ->and($unit->is_active)->toBeFalse();
});

test('unit abbreviation must be unique', function () {
    $admin = unitsAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $this->actingAs($admin)->post(route('inventario.units.store'), [
        'name' => 'Duplicada',
        'abbreviation' => 'kg',
        'unit_type' => 'weight',
        'is_active' => 1,
    ])->assertSessionHasErrors('abbreviation');
});
