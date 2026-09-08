<?php

use App\Models\ExchangeRate;
use App\Models\Role;
use App\Models\User;
use App\Services\PurchaseCostingService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function posReferenceAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('pos exposes usd reference rate for product cards while billing in company currency', function () {
    $admin = posReferenceAdmin();

    ExchangeRate::query()->create([
        'from_currency' => 'USD',
        'to_currency' => 'NIO',
        'rate' => 36.5,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $fx = app(PurchaseCostingService::class)->posReferenceFx();

    expect($fx['company_currency'])->toBe('NIO')
        ->and($fx['reference_currency'])->toBe('USD')
        ->and($fx['reference_symbol'])->toBe('US$')
        ->and($fx['reference_rate'])->toBe(round(1 / 36.5, 6));

    $this->actingAs($admin)
        ->get(route('facturacion.pos'))
        ->assertOk()
        ->assertViewHas('posReferenceFx', fn (array $data) => $data['reference_currency'] === 'USD'
            && $data['reference_rate'] === round(1 / 36.5, 6))
        ->assertSee('data-reference-currency="USD"', false)
        ->assertSee('data-reference-symbol="US$"', false)
        ->assertSee('id="totalReferenceDisplay"', false);
});
