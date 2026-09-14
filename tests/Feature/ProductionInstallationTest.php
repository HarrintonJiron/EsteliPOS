<?php

use App\Models\Account;
use App\Models\Client;
use App\Models\Module;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Tax;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    disableDemoSeederData();
});

test('the default seeder creates only production catalogs and no demo operations', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::where('slug', 'admin')->exists())->toBeTrue()
        ->and(Module::where('slug', 'configuracion')->exists())->toBeTrue()
        ->and(Setting::where('key', 'company_name')->exists())->toBeTrue()
        ->and(Tax::where('code', 'IVA-15')->exists())->toBeTrue()
        ->and(Account::where('code', '1.1.01')->exists())->toBeTrue()
        ->and(Product::count())->toBe(0)
        ->and(Client::count())->toBe(0)
        ->and(Sale::count())->toBe(0)
        ->and(Purchase::count())->toBe(0);

    $cashier = Role::where('slug', 'cajero')->firstOrFail();
    expect($cashier->hasPermission('ventas.view'))->toBeTrue()
        ->and($cashier->hasPermission('ventas.create'))->toBeTrue()
        ->and(Module::where('slug', 'ventas')->firstOrFail()->roles()->whereKey($cashier->id)->exists())->toBeTrue();
});

test('production catalogs can be seeded repeatedly without duplication', function () {
    $this->seed(DatabaseSeeder::class);

    $counts = [
        'roles' => Role::count(),
        'modules' => Module::count(),
        'accounts' => Account::count(),
        'taxes' => Tax::count(),
        'settings' => Setting::count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect(Role::count())->toBe($counts['roles'])
        ->and(Module::count())->toBe($counts['modules'])
        ->and(Account::count())->toBe($counts['accounts'])
        ->and(Tax::count())->toBe($counts['taxes'])
        ->and(Setting::count())->toBe($counts['settings']);
});
