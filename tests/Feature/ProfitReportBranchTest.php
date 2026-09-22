<?php

use App\Models\Branch;
use App\Models\BranchProductSource;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profit report separates results by branch and can filter one branch', function () {
    Carbon::setTestNow('2026-09-21 16:30:00');

    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);
    $client = Client::create(['name' => 'Cliente de rentabilidad', 'phone' => '0000-0000']);
    $category = Category::create(['name' => 'General']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto compartido',
        'code' => 'PROFIT-001',
        'purchase_price' => 30,
        'sale_price' => 60,
        'stock' => 20,
        'unit' => 'unidad',
    ]);
    $primary = Branch::create(['code' => 'PRI', 'name' => 'Principal', 'is_active' => true]);
    $secondary = Branch::create(['code' => 'S2', 'name' => 'Sucursal 2', 'is_active' => true]);

    foreach ([[$primary, 20], [$secondary, 40]] as [$branch, $sourceCost]) {
        BranchProductSource::create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'source_code' => 'SRC-'.$branch->code,
            'source_description' => 'Producto compartido',
            'source_stock' => 10,
            'source_cost' => $sourceCost,
            'sale_price_1' => 60,
            'unit_name' => 'unidad',
            'source_file' => 'prueba.xlsx',
            'source_row' => 2,
            'raw_data' => [],
        ]);
    }

    $primarySale = Sale::create([
        'invoice_number' => 'FAC-PRIMARY',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'branch_id' => $primary->id,
        'date' => '2026-09-21 10:00:00',
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);
    $secondarySale = Sale::create([
        'invoice_number' => 'FAC-SECONDARY',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'branch_id' => $secondary->id,
        'date' => '2026-09-21 11:00:00',
        'subtotal' => 120,
        'tax_total' => 0,
        'total' => 120,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);

    foreach ([[$primarySale, 50], [$secondarySale, 60]] as [$sale, $price]) {
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'base_quantity' => 2,
            'price' => $price,
            'subtotal' => $price * 2,
        ]);
    }

    $allBranches = $this->actingAs($admin)->get(route('reportes.index', [
        'report_type' => 'profit',
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-21',
    ]));

    $allBranches->assertOk()
        ->assertSee('Rentabilidad por sucursal')
        ->assertSee('FAC-PRIMARY')
        ->assertSee('FAC-SECONDARY');

    $summary = $allBranches->viewData('summary');
    $byBranch = $summary['by_branch']->keyBy('branch_id');

    expect((float) $summary['total_sales'])->toBe(220.0)
        ->and((float) $summary['total_cost'])->toBe(120.0)
        ->and((float) $summary['gross_profit'])->toBe(100.0)
        ->and($byBranch)->toHaveCount(2)
        ->and($byBranch[$primary->id]->gross_profit)->toBe(60.0)
        ->and($byBranch[$secondary->id]->gross_profit)->toBe(40.0);

    $filtered = $this->actingAs($admin)->get(route('reportes.index', [
        'report_type' => 'profit',
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-21',
        'branch_id' => $secondary->id,
    ]));

    $filtered->assertOk()
        ->assertSee('FAC-SECONDARY')
        ->assertDontSee('FAC-PRIMARY');

    $filteredSummary = $filtered->viewData('summary');
    expect((float) $filteredSummary['total_sales'])->toBe(120.0)
        ->and((float) $filteredSummary['total_cost'])->toBe(80.0)
        ->and((float) $filteredSummary['gross_profit'])->toBe(40.0)
        ->and($filteredSummary['by_branch'])->toHaveCount(1);

    Carbon::setTestNow();
});
