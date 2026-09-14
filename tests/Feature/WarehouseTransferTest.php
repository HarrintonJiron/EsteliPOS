<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function transferAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('internal transfers page loads and can move stock between warehouses', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = transferAdmin();
    $from = Warehouse::query()->where('is_default', true)->firstOrFail();
    $to = Warehouse::query()->where('is_default', false)->where('is_active', true)->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Transferencias UI']);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Bloque 6"',
        'code' => 'BLK-TRF-UI',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
    ]);

    app(InventoryService::class)->stockIn($product, 12, 'seed', 'Carga', $admin->id, $from->id);

    $this->actingAs($admin)
        ->get(route('inventario.transfers.index'))
        ->assertOk()
        ->assertSee('Transferencias internas');

    $this->actingAs($admin)->post(route('inventario.transfers.store'), [
        'product_id' => $product->id,
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'quantity' => 4,
        'note' => 'Reposición mostrador',
    ])->assertRedirect(route('inventario.transfers.index'))
        ->assertSessionHas('success');

    expect((float) $product->fresh()->stock)->toBe(12.0)
        ->and((float) $product->stockInWarehouse($from->id))->toBe(8.0)
        ->and((float) $product->stockInWarehouse($to->id))->toBe(4.0);

    $this->actingAs($admin)
        ->get(route('inventario.transfers.index'))
        ->assertOk()
        ->assertSee('Bloque 6"')
        ->assertSee('Reposición mostrador');
});

test('inventory reconciliation uses warehouse totals instead of incomplete movement history', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Reconciliación QA']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Inventario heredado',
        'code' => 'QA-LEGACY-STOCK',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 3,
        'unit' => 'und',
        'status' => 'active',
    ]);
    WarehouseStock::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 20,
    ]);

    $result = app(InventoryService::class)->reconcileAll(true);

    expect($result['fixed'])->toBe(1)
        ->and((float) $product->fresh()->stock)->toBe(20.0);
});

test('inventory reconciliation endpoint audits corrected values', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = transferAdmin();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Auditoría reconciliación']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto auditable',
        'code' => 'QA-AUDIT-STOCK',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 2,
        'unit' => 'und',
        'status' => 'active',
    ]);
    WarehouseStock::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 9,
    ]);

    $this->actingAs($admin)
        ->post(route('inventario.reconcile'))
        ->assertRedirect(route('inventario.dashboard'));

    $audit = AuditLog::query()->where('action', 'inventory.reconciled')->firstOrFail();
    expect((float) $product->fresh()->stock)->toBe(9.0)
        ->and($audit->new_values['products'][0])->toMatchArray([
            'product_id' => $product->id,
            'recorded' => 2.0,
            'corrected' => 9.0,
        ]);
});
