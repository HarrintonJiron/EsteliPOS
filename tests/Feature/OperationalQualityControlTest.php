<?php

use App\Models\Category;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseShelf;
use App\Models\WarehouseStock;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function qaControlAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

/**
 * @return array{admin: User, product: Product, und: Unit, ristra: Unit, caja: Unit, warehouse: Warehouse}
 */
function qaPresentedProduct(): array
{
    test()->seed(InventoryCatalogSeeder::class);
    $admin = qaControlAdmin();

    $und = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $ristra = Unit::query()->where('abbreviation', 'ristra')->firstOrFail();
    $caja = Unit::query()->where('abbreviation', 'caja')->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Control de calidad']);
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Jabón de tocador',
        'code' => 'QA-JABON-1',
        'purchase_price' => 5.5,
        'sale_price' => 8,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $und->id,
        'status' => 'active',
    ]);

    test()->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $ristra->id,
        'equals_base_qty' => 3,
        'equals_unit_id' => $und->id,
        'is_default_sale_unit' => 1,
    ])->assertRedirect()->assertSessionHas('success');

    test()->actingAs($admin)->post(route('inventario.conversions.store', $product->id), [
        'unit_id' => $caja->id,
        'equals_base_qty' => 12,
        'equals_unit_id' => $ristra->id,
        'sale_price' => 270,
    ])->assertRedirect()->assertSessionHas('success');

    return [
        'admin' => $admin,
        'product' => $product->fresh(['unitConversions']),
        'und' => $und,
        'ristra' => $ristra,
        'caja' => $caja,
        'warehouse' => $warehouse,
    ];
}

function qaPostedPurchaseCreditAccount(Purchase $purchase): ?string
{
    $entry = JournalEntry::query()
        ->with('lines.account')
        ->where('source_type', Purchase::class)
        ->where('source_id', $purchase->id)
        ->where('status', JournalEntry::STATUS_POSTED)
        ->first();

    return $entry?->lines
        ->first(fn ($line): bool => (float) $line->credit > 0)
        ?->account
        ?->code;
}

test('the general dashboard loads with pin switch users and ignores users without a pin', function () {
    $admin = qaControlAdmin();
    $cashierWithPin = User::factory()->create([
        'name' => 'Cajero con PIN QA',
        'username' => 'cajero.pin.qa',
        'pin_hash' => Hash::make('2580'),
        'is_active' => true,
    ]);
    User::factory()->create([
        'name' => 'Cajero sin PIN QA',
        'username' => 'cajero.sin.pin.qa',
        'pin_hash' => null,
        'is_active' => true,
    ]);

    expect(Schema::hasColumn('users', 'pin_hash'))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('dashboard.general'))
        ->assertOk()
        ->assertSee('Cambiar de usuario')
        ->assertSee('Cajero con PIN QA')
        ->assertSee('@cajero.pin.qa', false)
        ->assertDontSee('Cajero sin PIN QA')
        ->assertDontSee('@cajero.sin.pin.qa', false)
        ->assertSee(route('auth.switch-user'), false);

    expect($cashierWithPin->fresh()->is_active)->toBeTrue();
});

test('the pos sells a box presentation and deducts stock in base units', function () {
    $context = qaPresentedProduct();
    $warehouse = $context['warehouse'];
    $product = $context['product'];
    $caja = $context['caja'];

    app(InventoryService::class)->stockIn(
        $product,
        72,
        'qa-in',
        'Compra de 2 cajas',
        $context['admin']->id,
        $warehouse->id,
    );

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($context['admin'])->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'warehouse_id' => $warehouse->id,
        'items' => json_encode([[
            'product_id' => $product->id,
            'unit_id' => $caja->id,
            'quantity' => 1,
            'discount' => 0,
        ]]),
        'amount_received' => 270,
        'order_discount_pct' => 0,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->with('details')->latest('id')->firstOrFail();

    expect((float) $sale->details->first()->quantity)->toBe(1.0)
        ->and((int) $sale->details->first()->unit_id)->toBe($caja->id)
        ->and((float) $sale->details->first()->price)->toBe(270.0)
        ->and((float) $product->fresh()->stock)->toBe(36.0);
});

test('a sale keeps its original base quantity when a presentation changes later', function () {
    $context = qaPresentedProduct();
    $product = $context['product'];

    app(InventoryService::class)->stockIn(
        $product,
        72,
        'qa-history-in',
        'Stock para probar historial de presentaciones',
        $context['admin']->id,
        $context['warehouse']->id,
    );

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    $accounting->shouldReceive('voidForSource')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($context['admin'])->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'warehouse_id' => $context['warehouse']->id,
        'items' => json_encode([[
            'product_id' => $product->id,
            'unit_id' => $context['caja']->id,
            'quantity' => 1,
            'discount' => 0,
        ]]),
        'amount_received' => 270,
        'order_discount_pct' => 0,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->with('details')->latest('id')->firstOrFail();
    $detail = $sale->details->first();

    expect((float) $detail->unit_factor)->toBe(36.0)
        ->and((float) $detail->base_quantity)->toBe(36.0)
        ->and((float) $product->fresh()->stock)->toBe(36.0);

    $product->unitConversions()->where('unit_id', $context['caja']->id)->update(['factor_to_base' => 30]);

    $this->actingAs($context['admin'])
        ->delete(route('facturacion.destroy', $sale->id))
        ->assertRedirect(route('facturacion.index'));

    expect((float) $product->fresh()->stock)->toBe(72.0);
});

test('the pos refuses a box sale when base stock is insufficient', function () {
    $context = qaPresentedProduct();
    $warehouse = $context['warehouse'];
    $product = $context['product'];
    $caja = $context['caja'];

    app(InventoryService::class)->stockIn(
        $product,
        20,
        'qa-in-short',
        'Stock insuficiente para una caja',
        $context['admin']->id,
        $warehouse->id,
    );

    $this->actingAs($context['admin'])
        ->from(route('facturacion.pos'))
        ->post(route('facturacion.pos-store'), [
            'payment_type' => 'cash',
            'warehouse_id' => $warehouse->id,
            'items' => json_encode([[
                'product_id' => $product->id,
                'unit_id' => $caja->id,
                'quantity' => 1,
                'discount' => 0,
            ]]),
            'amount_received' => 270,
            'order_discount_pct' => 0,
        ])
        ->assertRedirect(route('facturacion.pos'))
        ->assertSessionHasErrors();

    expect(Sale::query()->count())->toBe(0)
        ->and((float) $product->fresh()->stock)->toBe(20.0);
});

test('the pos refuses fractional boxes unless the presentation allows them', function () {
    $context = qaPresentedProduct();

    app(InventoryService::class)->stockIn(
        $context['product'],
        72,
        'qa-in-fraction',
        'Stock para validar cajas enteras',
        $context['admin']->id,
        $context['warehouse']->id,
    );

    $this->actingAs($context['admin'])
        ->from(route('facturacion.pos'))
        ->post(route('facturacion.pos-store'), [
            'payment_type' => 'cash',
            'warehouse_id' => $context['warehouse']->id,
            'items' => json_encode([[
                'product_id' => $context['product']->id,
                'unit_id' => $context['caja']->id,
                'quantity' => 0.5,
                'discount' => 0,
            ]]),
            'amount_received' => 270,
            'order_discount_pct' => 0,
        ])
        ->assertRedirect(route('facturacion.pos'))
        ->assertSessionHasErrors('items');

    expect(Sale::query()->count())->toBe(0)
        ->and((float) $context['product']->fresh()->stock)->toBe(72.0);
});

test('the pos invoices two presentations of one product and deducts their combined base quantity', function () {
    $context = qaPresentedProduct();
    $warehouse = $context['warehouse'];
    $product = $context['product'];

    app(InventoryService::class)->stockIn(
        $product,
        72,
        'qa-in-dup',
        'Stock para dos presentaciones',
        $context['admin']->id,
        $warehouse->id,
    );

    $this->actingAs($context['admin'])
        ->from(route('facturacion.pos'))
        ->post(route('facturacion.pos-store'), [
            'payment_type' => 'cash',
            'warehouse_id' => $warehouse->id,
            'items' => json_encode([
                [
                    'product_id' => $product->id,
                    'unit_id' => $context['ristra']->id,
                    'quantity' => 1,
                    'discount' => 0,
                ],
                [
                    'product_id' => $product->id,
                    'unit_id' => $context['caja']->id,
                    'quantity' => 1,
                    'discount' => 0,
                ],
            ]),
            'amount_received' => 300,
            'order_discount_pct' => 0,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $sale = Sale::query()->with('details')->latest('id')->firstOrFail();

    expect($sale->details)->toHaveCount(2)
        ->and($sale->details->pluck('unit_id')->all())->toContain($context['ristra']->id, $context['caja']->id)
        ->and((float) $sale->details->sum('base_quantity'))->toBe(39.0)
        ->and((float) $product->fresh()->stock)->toBe(33.0)
        ->and((float) WarehouseStock::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity'))->toBe(33.0);
});

test('the pos screen exposes the presentation selector for products with sale units', function () {
    $context = qaPresentedProduct();

    app(InventoryService::class)->stockIn(
        $context['product'],
        36,
        'qa-in-pos',
        'Stock visible en caja',
        $context['admin']->id,
        $context['warehouse']->id,
    );

    $this->actingAs($context['admin'])
        ->get(route('facturacion.pos'))
        ->assertOk()
        ->assertSee('data-product-unit-select', false)
        ->assertSee('Presentación')
        ->assertSee('QA-JABON-1');
});

test('the purchase create form keeps supplier search and payment type after merge', function () {
    $this->seed(ConfigurationSeeder::class);
    $this->seed(InventoryCatalogSeeder::class);
    $admin = qaControlAdmin();

    $this->actingAs($admin)
        ->get(route('compras.create'))
        ->assertOk()
        ->assertSee('Buscar proveedor')
        ->assertSee('id="supplierSearch"', false)
        ->assertSee('name="payment_type"', false)
        ->assertSee('A crédito (por pagar)')
        ->assertSee('Contado (efectivo)');
});

test('a cash purchase from the form settles immediately and credits cash', function () {
    $this->seed(ConfigurationSeeder::class);
    $this->seed(InventoryCatalogSeeder::class);
    $admin = qaControlAdmin();

    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplier = Supplier::query()->create(['name' => 'Distribuidora Contado QA', 'status' => 'active']);
    $category = Category::firstOrCreate(['name' => 'Compras QA']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Cloro 5L QA',
        'code' => 'QA-COMP-CASH-1',
        'purchase_price' => 20,
        'sale_price' => 30,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('compras.store'), [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'payment_type' => 'cash',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 4,
            'price' => 20,
        ]],
    ])->assertRedirect(route('compras.index'));

    $purchase = Purchase::query()->firstOrFail();

    expect($purchase->status)->toBe('completed')
        ->and($purchase->payment_type)->toBe('cash')
        ->and((float) $product->fresh()->stock)->toBe(4.0)
        ->and(qaPostedPurchaseCreditAccount($purchase))->toBe('1.1.01');
});

test('the full product form can assign initial stock to a warehouse shelf', function () {
    $this->seed(InventoryCatalogSeeder::class);
    $admin = qaControlAdmin();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $category = Category::firstOrCreate(['name' => 'Estantes QA']);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();

    $this->actingAs($admin)->post(route('inventario.warehouses.shelves.store', $warehouse), [
        'code' => 'QA-A-01',
        'name' => 'Agroquímicos QA',
    ])->assertRedirect()->assertSessionHas('success');

    $shelf = WarehouseShelf::query()
        ->where('warehouse_id', $warehouse->id)
        ->where('code', 'QA-A-01')
        ->firstOrFail();

    $this->actingAs($admin)->post(route('inventario.store'), [
        'category_id' => $category->id,
        'name' => 'Fertilizante en estante QA',
        'code' => 'QA-EST-001',
        'purchase_price' => 100,
        'sale_price' => 150,
        'stock' => 8,
        'base_unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'shelf_id' => $shelf->id,
        'status' => 'active',
    ])->assertRedirect(route('inventario.create'))->assertSessionHasNoErrors();

    $product = Product::query()->where('code', 'QA-EST-001')->firstOrFail();

    expect((float) $product->stock)->toBe(8.0)
        ->and(WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('aisle'))->toBe('QA-A-01');
});

test('authenticated operators can open help from an authenticated layout', function () {
    $admin = qaControlAdmin();

    $this->actingAs($admin)
        ->get(route('help.index'))
        ->assertOk()
        ->assertSee('Centro de ayuda')
        ->assertSee('Ayuda sin Internet')
        ->assertSee(route('auth.switch-user'), false);
});
