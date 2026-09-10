<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function monetaryGuardAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

function monetaryProductPayload(float $salePrice, bool $confirmed = false): array
{
    $payload = [
        'category_id' => Category::query()->firstOrCreate(['name' => 'Montos'])->id,
        'name' => 'Producto monto controlado',
        'code' => 'MONTO-'.str()->random(8),
        'purchase_price' => 100,
        'sale_price' => $salePrice,
        'stock' => 0,
        'base_unit_id' => Unit::query()->firstOrFail()->id,
        'status' => 'active',
    ];

    if ($confirmed) {
        $payload['large_amount_confirmed'] = '1';
    }

    return $payload;
}

beforeEach(function () {
    $this->seed(InventoryCatalogSeeder::class);
});

test('an implausible product price is blocked before it can affect the database', function () {
    $admin = monetaryGuardAdmin();

    $this->actingAs($admin)
        ->from(route('inventario.create'))
        ->post(route('inventario.store'), monetaryProductPayload(7_000_000_000))
        ->assertRedirect(route('inventario.create'))
        ->assertSessionHasErrors('sale_price');

    expect(Product::query()->where('name', 'Producto monto controlado')->exists())->toBeFalse();
});

test('an unusually high price requires explicit confirmation', function () {
    $admin = monetaryGuardAdmin();
    $payload = monetaryProductPayload(1_500_000);

    $this->actingAs($admin)->post(route('inventario.store'), $payload)
        ->assertSessionHasErrors('sale_price');

    $this->actingAs($admin)->post(route('inventario.store'), array_merge($payload, [
        'code' => 'MONTO-CONFIRMADO',
        'large_amount_confirmed' => '1',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('inventario.create'));

    expect((float) Product::query()->where('code', 'MONTO-CONFIRMADO')->value('sale_price'))->toBe(1_500_000.0);
});
