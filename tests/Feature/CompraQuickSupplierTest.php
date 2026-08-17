<?php

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('purchase form can create a supplier quickly', function () {
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->postJson(route('compras.suppliers.quick-store'), [
            'name' => 'Agroquímicos Express',
            'phone' => '88887766',
            'ruc' => 'J0310000000001',
            'contact_name' => 'Ana Pérez',
            'address' => 'Estelí',
            'payment_condition' => 'contado',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('supplier.name', 'Agroquímicos Express');

    $supplier = Supplier::query()->where('name', 'Agroquímicos Express')->firstOrFail();

    expect($supplier->status)->toBe('active')
        ->and($supplier->phone)->toBe('88887766')
        ->and($supplier->payment_condition)->toBe('contado')
        ->and($supplier->code)->toStartWith('PR-');
});

test('quick supplier requires a name', function () {
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->postJson(route('compras.suppliers.quick-store'), [
            'phone' => '88887766',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('nueva compra shows quick supplier option', function () {
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('compras.create'))
        ->assertOk()
        ->assertSee('openQuickSupplier', false)
        ->assertSee('Proveedor rápido')
        ->assertSee(route('compras.suppliers.quick-store'), false);
});
