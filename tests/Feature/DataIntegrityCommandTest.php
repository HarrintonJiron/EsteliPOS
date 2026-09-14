<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\JournalEntry;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('integrity command passes on a clean database', function () {
    $this->artisan('app:check-integrity')
        ->expectsOutputToContain('[OK] No se detectaron inconsistencias de datos.')
        ->assertSuccessful();
});

test('integrity command fails when a posted journal entry is unbalanced', function () {
    $user = User::factory()->create();
    JournalEntry::query()->create([
        'number' => 'POL-QA-000001',
        'date' => now()->toDateString(),
        'concept' => 'Asiento deliberadamente inválido para QA',
        'status' => JournalEntry::STATUS_POSTED,
        'total_debit' => 100,
        'total_credit' => 0,
        'user_id' => $user->id,
        'posted_at' => now(),
    ]);

    $this->artisan('app:check-integrity')
        ->expectsOutputToContain('[ERROR]')
        ->assertFailed();
});

test('integrity command detects stock that differs from warehouse totals', function () {
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'QA integridad',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('products')->insert([
        'category_id' => $categoryId,
        'name' => 'Producto con diferencia',
        'code' => 'QA-STOCK-MISMATCH',
        'purchase_price' => 1,
        'sale_price' => 2,
        'stock' => 5,
        'unit' => 'und',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('app:check-integrity')
        ->expectsOutputToContain('Stock global diferente a suma de bodegas')
        ->assertFailed();
});

test('inventory reconciliation command previews by default and applies with an audit trail', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'QA reconciliación CLI']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto CLI',
        'code' => 'QA-RECONCILE-CLI',
        'purchase_price' => 1,
        'sale_price' => 2,
        'stock' => 5,
        'unit' => 'und',
        'status' => 'active',
    ]);
    WarehouseStock::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 12,
    ]);

    $this->artisan('app:reconcile-inventory')
        ->expectsOutputToContain('[VISTA PREVIA]')
        ->assertFailed();
    expect((float) $product->fresh()->stock)->toBe(5.0);

    $this->artisan('app:reconcile-inventory --apply')
        ->expectsOutputToContain('[OK] Se corrigieron 1 productos')
        ->assertSuccessful();

    expect((float) $product->fresh()->stock)->toBe(12.0)
        ->and(AuditLog::query()->where('action', 'inventory.reconciled.command')->exists())->toBeTrue();
});

test('production certification passes for a healthy test environment', function () {
    config(['app.debug' => false]);
    foreach (['factura', 'compra', 'cotizacion', 'proforma', 'reparacion', 'recibo', 'ajuste', 'asiento'] as $type) {
        NumberSequence::query()->updateOrCreate(['type' => $type], [
            'prefix' => strtoupper(substr($type, 0, 3)).'-',
            'current_number' => 1,
            'padding' => 6,
            'is_active' => true,
        ]);
    }

    $this->artisan('app:production-certify --allow-non-production')
        ->expectsOutputToContain('[APROBADO]')
        ->assertSuccessful();
});
