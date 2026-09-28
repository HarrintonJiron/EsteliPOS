<?php

use App\Models\Arqueo;
use App\Models\Category;
use App\Models\Client;
use App\Models\Employee;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntry;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\ComprehensiveDemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the comprehensive demo dataset covers the main system areas without duplicating itself', function () {
    $this->seed(ProductionSeeder::class);

    $admin = User::factory()->create([
        'name' => 'Administrador de prueba',
        'email' => 'admin.seeder@local.test',
        'role' => 'admin',
        'is_active' => true,
    ]);
    $admin->roles()->sync([Role::where('slug', 'admin')->firstOrFail()->id]);

    $this->seed(ComprehensiveDemoSeeder::class);

    expect(Category::count())->toBeGreaterThanOrEqual(10)
        ->and(Supplier::count())->toBe(10)
        ->and(Client::count())->toBe(15)
        ->and(Product::count())->toBe(20)
        ->and(Sale::count())->toBe(15)
        ->and(Purchase::count())->toBe(10)
        ->and(Proforma::count())->toBe(10)
        ->and(RepairOrder::count())->toBe(10)
        ->and(Employee::count())->toBe(10)
        ->and(Payroll::count())->toBe(20)
        ->and(InventoryAdjustment::count())->toBe(6)
        ->and(Arqueo::count())->toBe(5)
        ->and(JournalEntry::count())->toBeGreaterThanOrEqual(31)
        ->and(Setting::where('key', 'demo.comprehensive.loaded_at')->exists())->toBeTrue();

    $discrepancies = Product::all()
        ->filter(fn (Product $product) => app(InventoryService::class)->calculatedStock($product) !== (int) $product->stock);

    expect($discrepancies)->toBeEmpty()
        ->and(JournalEntry::with('lines')->get()->every->isBalanced())->toBeTrue();

    $countsBeforeSecondRun = [Sale::count(), Purchase::count(), RepairOrder::count()];
    $this->seed(ComprehensiveDemoSeeder::class);

    expect([Sale::count(), Purchase::count(), RepairOrder::count()])->toBe($countsBeforeSecondRun);
});
