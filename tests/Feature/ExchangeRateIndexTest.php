<?php

use App\Models\ExchangeRate;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function exchangeRateAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $adminRole = Role::query()->where('slug', 'admin')->first();
    if ($adminRole) {
        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }

    return $user;
}

test('exchange rates index renders rates without treating groups as models', function () {
    $admin = exchangeRateAdmin();

    ExchangeRate::query()->create([
        'from_currency' => 'USD',
        'to_currency' => 'NIO',
        'rate' => 36.6243,
        'effective_date' => now()->subDay()->toDateString(),
        'is_active' => true,
    ]);

    ExchangeRate::query()->create([
        'from_currency' => 'USD',
        'to_currency' => 'NIO',
        'rate' => 36.8000,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    ExchangeRate::query()->create([
        'from_currency' => 'EUR',
        'to_currency' => 'NIO',
        'rate' => 40.0000,
        'effective_date' => now()->toDateString(),
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('settings.exchange-rates.index'))
        ->assertOk()
        ->assertSee('USD')
        ->assertSee('NIO')
        ->assertSee('36.800000')
        ->assertSee('36.624300')
        ->assertSee('EUR')
        ->assertSee('Inactivo');
});
