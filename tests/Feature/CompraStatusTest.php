<?php

use App\Models\Category;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function compraStatusAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

function postedPurchaseCreditAccount(Purchase $purchase): ?string
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

/**
 * @return array{admin: User, product: Product, purchase: Purchase, warehouse: Warehouse}
 */
function pendingPurchase(): array
{
    $admin = compraStatusAdmin();
    test()->seed(InventoryCatalogSeeder::class);

    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplier = Supplier::query()->create(['name' => 'Distribuidora Pendiente', 'status' => 'active']);
    $category = Category::firstOrCreate(['name' => 'Compras']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Cloro 5L',
        'code' => 'COMP-PEND-1',
        'purchase_price' => 20,
        'sale_price' => 30,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    test()->actingAs($admin)->post(route('compras.store'), [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
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

    expect((float) $product->fresh()->stock)->toBe(4.0)
        ->and($purchase->status)->toBe('pending')
        ->and($purchase->payment_type)->toBe('credit')
        ->and(postedPurchaseCreditAccount($purchase))->toBe('2.1.01');

    return [
        'admin' => $admin,
        'product' => $product->fresh(),
        'purchase' => $purchase,
        'warehouse' => $warehouse,
    ];
}

test('pending credit purchase show page offers pay and cancel actions', function () {
    $context = pendingPurchase();

    $this->actingAs($context['admin'])
        ->get(route('compras.show', $context['purchase']->id))
        ->assertOk()
        ->assertSee('Por pagar')
        ->assertSee('Anular')
        ->assertSee('Pagar')
        ->assertSee('Mercadería en inventario')
        ->assertDontSee('Aún no entra al inventario');
});

test('a credit purchase can be canceled and stock is reversed', function () {
    $context = pendingPurchase();

    $this->actingAs($context['admin'])
        ->from(route('compras.show', $context['purchase']->id))
        ->post(route('compras.status', $context['purchase']->id), ['status' => 'canceled'])
        ->assertRedirect(route('compras.show', $context['purchase']->id))
        ->assertSessionHas('success');

    expect($context['purchase']->fresh()->status)->toBe('canceled')
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0)
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $context['purchase']->id)->where('status', JournalEntry::STATUS_POSTED)->exists())->toBeFalse();
});

test('a credit purchase can be paid without moving stock twice and then canceled', function () {
    $context = pendingPurchase();

    $this->actingAs($context['admin'])
        ->from(route('compras.show', $context['purchase']->id))
        ->post(route('compras.status', $context['purchase']->id), [
            'status' => 'completed',
            'payment_type' => 'cash',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $paid = $context['purchase']->fresh();

    expect($paid->status)->toBe('completed')
        ->and($paid->payment_type)->toBe('cash')
        ->and((float) $context['product']->fresh()->stock)->toBe(4.0)
        ->and(postedPurchaseCreditAccount($paid))->toBe('2.1.01');

    $payment = SupplierPayment::query()->where('purchase_id', $paid->id)->firstOrFail();
    $paymentEntry = JournalEntry::query()
        ->with('lines.account')
        ->where('source_type', SupplierPayment::class)
        ->where('source_id', $payment->id)
        ->where('status', JournalEntry::STATUS_POSTED)
        ->firstOrFail();

    expect($paymentEntry->lines->firstWhere('account.code', '2.1.01')?->debit)->toBe('80.00')
        ->and($paymentEntry->lines->firstWhere('account.code', '1.1.01')?->credit)->toBe('80.00');

    $this->actingAs($context['admin'])
        ->from(route('compras.show', $context['purchase']->id))
        ->post(route('compras.status', $context['purchase']->id), ['status' => 'canceled'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($context['purchase']->fresh()->status)->toBe('canceled')
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0)
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $context['purchase']->id)->where('status', JournalEntry::STATUS_POSTED)->exists())->toBeFalse()
        ->and($payment->fresh()->status)->toBe('canceled')
        ->and($paymentEntry->fresh()->status)->toBe(JournalEntry::STATUS_VOIDED);
});

test('a canceled purchase cannot be paid from the status action', function () {
    $context = pendingPurchase();

    $this->actingAs($context['admin'])
        ->post(route('compras.status', $context['purchase']->id), ['status' => 'canceled'])
        ->assertRedirect();

    $this->actingAs($context['admin'])
        ->from(route('compras.show', $context['purchase']->id))
        ->post(route('compras.status', $context['purchase']->id), [
            'status' => 'completed',
            'payment_type' => 'cash',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($context['purchase']->fresh()->status)->toBe('canceled')
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0);
});

test('a credit purchase cannot exceed the supplier credit limit', function () {
    $admin = compraStatusAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplier = Supplier::query()->create([
        'name' => 'Proveedor con techo',
        'status' => 'active',
        'credit_limit' => 50,
    ]);
    $category = Category::firstOrCreate(['name' => 'Compras']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Saco de cal',
        'code' => 'COMP-LIM-1',
        'purchase_price' => 20,
        'sale_price' => 30,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->from(route('compras.create'))
        ->post(route('compras.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'date' => now()->toDateString(),
            'payment_type' => 'credit',
            'currency' => 'NIO',
            'exchange_rate' => 1,
            'items' => [[
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 4,
                'price' => 20,
            ]],
        ])
        ->assertRedirect(route('compras.create'))
        ->assertSessionHas('error');

    expect(Purchase::query()->count())->toBe(0)
        ->and((float) $product->fresh()->stock)->toBe(0.0);
});
