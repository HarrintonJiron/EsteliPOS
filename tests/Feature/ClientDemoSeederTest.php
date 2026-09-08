<?php

use App\Models\Branch;
use App\Models\CajaSession;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\CreditPayment;
use App\Models\Employee;
use App\Models\FiscalPeriod;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntry;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseShelf;
use App\Models\WarehouseStock;
use Database\Seeders\ClientDemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads a rich ferreteria catalog for client demonstrations', function () {
    $this->seed(ProductionSeeder::class);
    User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->seed(ClientDemoSeeder::class);

    expect(Product::count())->toBeGreaterThan(150)
        ->and(Client::count())->toBeGreaterThan(25)
        ->and(Supplier::count())->toBeGreaterThan(10)
        ->and(Sale::count())->toBeGreaterThan(60)
        ->and(Purchase::count())->toBeGreaterThan(25)
        ->and(CreditPayment::count())->toBeGreaterThan(5)
        ->and(WarehouseStock::count())->toBeGreaterThan(80)
        ->and(Product::query()->whereNotNull('base_unit_id')->count())->toBe(Product::count())
        ->and(Product::query()->where('name', 'Jabón de baño')->exists())->toBeTrue()
        ->and(Product::query()->where('name', 'Jabón de baño')->first()?->unitConversions()->count())->toBe(2)
        ->and(Product::query()->where('image_url', 'like', 'http%')->count())->toBe(0)
        ->and(Product::query()->whereNull('image_url')->count())->toBe(0)
        ->and(Product::query()->where('image_url', 'like', 'products/demo-%')->count())->toBe(Product::count())
        ->and(Product::query()->distinct()->count('image_url'))->toBeLessThan(Product::count())
        ->and(Sale::query()->whereNotNull('invoice_number')->count())->toBe(Sale::count())
        ->and(Employee::count())->toBeGreaterThan(6)
        ->and(Payroll::count())->toBeGreaterThan(10)
        ->and(Proforma::count())->toBeGreaterThan(10)
        ->and(RepairOrder::count())->toBeGreaterThan(8)
        ->and(CajaSession::count())->toBeGreaterThan(8)
        ->and(InventoryAdjustment::count())->toBeGreaterThan(4)
        ->and(WarehouseShelf::count())->toBeGreaterThan(8)
        ->and(JournalEntry::count())->toBeGreaterThan(40)
        ->and(CajaSession::query()->where('status', 'open')->count())->toBe(1)
        ->and(CostCenter::count())->toBeGreaterThan(4)
        ->and(JournalEntry::query()->where('reference', 'like', 'DEMO-CONT-%')->count())->toBeGreaterThan(10)
        ->and(JournalEntry::query()->where('status', JournalEntry::STATUS_DRAFT)->count())->toBeGreaterThan(1)
        ->and(JournalEntry::query()->where('status', JournalEntry::STATUS_VOIDED)->count())->toBeGreaterThan(0)
        ->and(FiscalPeriod::query()->monthly()->closed()->count())->toBeGreaterThan(3)
        ->and(Branch::count())->toBeGreaterThan(3);
});

it('does not duplicate demonstration data on a second run', function () {
    $this->seed(ProductionSeeder::class);
    User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->seed(ClientDemoSeeder::class);

    $counts = [
        'products' => Product::count(),
        'clients' => Client::count(),
        'sales' => Sale::count(),
        'purchases' => Purchase::count(),
        'employees' => Employee::count(),
        'proformas' => Proforma::count(),
        'repairs' => RepairOrder::count(),
        'caja' => CajaSession::count(),
        'journals' => JournalEntry::count(),
        'cost_centers' => CostCenter::count(),
        'closed_periods' => FiscalPeriod::query()->monthly()->closed()->count(),
        'drafts' => JournalEntry::query()->where('status', JournalEntry::STATUS_DRAFT)->count(),
    ];

    $this->seed(ClientDemoSeeder::class);

    expect(Product::count())->toBe($counts['products'])
        ->and(Client::count())->toBe($counts['clients'])
        ->and(Sale::count())->toBe($counts['sales'])
        ->and(Purchase::count())->toBe($counts['purchases'])
        ->and(Employee::count())->toBe($counts['employees'])
        ->and(Proforma::count())->toBe($counts['proformas'])
        ->and(RepairOrder::count())->toBe($counts['repairs'])
        ->and(CajaSession::count())->toBe($counts['caja'])
        ->and(JournalEntry::count())->toBe($counts['journals'])
        ->and(CostCenter::count())->toBe($counts['cost_centers'])
        ->and(FiscalPeriod::query()->monthly()->closed()->count())->toBe($counts['closed_periods'])
        ->and(JournalEntry::query()->where('status', JournalEntry::STATUS_DRAFT)->count())->toBe($counts['drafts']);
});
