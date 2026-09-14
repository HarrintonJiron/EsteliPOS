<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function proformaAuditContext(): array
{
    test()->seed(ConfigurationSeeder::class);
    test()->seed(InventoryCatalogSeeder::class);

    $role = Role::query()->where('slug', 'admin')->firstOrFail();
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);
    openCashSessionFor($admin);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplier = Supplier::query()->create([
        'name' => 'Proveedor QA',
        'status' => 'active',
        'credit_limit' => 0,
    ]);
    $category = Category::firstOrCreate(['name' => 'QA proformas']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto QA',
        'code' => 'QA-PROF-001',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    return compact('admin', 'unit', 'warehouse', 'supplier', 'category', 'product');
}

function createAuditProforma(array $context, array $overrides = []): Purchase
{
    $payload = array_replace_recursive([
        'supplier_id' => $context['supplier']->id,
        'warehouse_id' => $context['warehouse']->id,
        'date' => now()->toDateString(),
        'purchase_mode' => 'proforma',
        'payment_type' => 'credit',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $context['product']->id,
            'unit_id' => $context['unit']->id,
            'quantity' => 2,
            'price' => 10,
        ]],
    ], $overrides);

    test()->actingAs($context['admin'])
        ->post(route('compras.store'), $payload)
        ->assertRedirect(route('compras.index'));

    return Purchase::query()->latest('id')->firstOrFail();
}

test('guests cannot access purchase proforma endpoints', function () {
    foreach ([
        ['get', route('compras.index')],
        ['get', route('compras.proformas.create')],
        ['post', route('compras.store')],
        ['get', route('compras.proformas.ticket', 1)],
        ['get', route('compras.proformas.pdf', 1)],
        ['post', route('compras.status', 1)],
        ['delete', route('compras.proformas.details.destroy', [1, 1])],
    ] as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }
});

test('a view-only user can read but cannot mutate proformas', function () {
    $context = proformaAuditContext();
    $purchase = createAuditProforma($context);

    $viewerRole = Role::query()->create([
        'name' => 'Consulta compras QA',
        'slug' => 'qa-compras-view',
        'is_system' => false,
    ]);
    $viewerRole->permissions()->sync([
        Permission::query()->where('slug', 'compras.view')->firstOrFail()->id,
    ]);
    Module::query()->where('slug', 'compras')->firstOrFail()->roles()->syncWithoutDetaching([$viewerRole->id]);
    $viewer = User::factory()->create([
        'role' => 'qa-compras-view',
        'is_active' => true,
        'force_password_change' => false,
    ]);
    $viewer->roles()->sync([$viewerRole->id]);

    $this->actingAs($viewer)->get(route('compras.index'))->assertOk();
    $this->actingAs($viewer)->get(route('compras.show', $purchase))->assertOk();
    $this->actingAs($viewer)->get(route('compras.proformas.ticket', $purchase))->assertOk();
    $this->actingAs($viewer)->get(route('compras.proformas.pdf', $purchase))->assertOk();
    $this->actingAs($viewer)
        ->get(route('compras.proformas.create'))
        ->assertRedirect(route('compras.index'))
        ->assertSessionHas('error');
    $this->actingAs($viewer)
        ->get(route('compras.edit', $purchase))
        ->assertRedirect(route('compras.index'))
        ->assertSessionHas('error');
    $this->actingAs($viewer)->post(route('compras.status', $purchase), [
        'status' => 'received',
        'payment_type' => 'cash',
    ])->assertRedirect(route('compras.index'))->assertSessionHas('error');
    $this->actingAs($viewer)->delete(route('compras.proformas.details.destroy', [
        $purchase,
        $purchase->details()->value('id'),
    ]))->assertRedirect(route('compras.index'))->assertSessionHas('error');
    $this->actingAs($viewer)
        ->delete(route('compras.destroy', $purchase))
        ->assertRedirect(route('compras.index'))
        ->assertSessionHas('error');

    expect($purchase->fresh()->status)->toBe('ordered')
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0);
});

test('print-only proforma endpoints reject ordinary purchases', function () {
    $context = proformaAuditContext();
    $purchase = createAuditProforma($context);
    $purchase->update(['status' => 'completed', 'payment_type' => 'cash']);

    $this->actingAs($context['admin'])
        ->get(route('compras.proformas.ticket', $purchase))
        ->assertNotFound();
    $this->actingAs($context['admin'])
        ->get(route('compras.proformas.pdf', $purchase))
        ->assertNotFound();
});

test('a detail from another proforma cannot be removed through a different parent id', function () {
    $context = proformaAuditContext();
    $first = createAuditProforma($context);
    $second = createAuditProforma($context);

    $this->actingAs($context['admin'])
        ->delete(route('compras.proformas.details.destroy', [$first, $second->details()->value('id')]))
        ->assertNotFound();

    expect(PurchaseDetail::query()->count())->toBe(2);
});

test('canceling an open proforma has no inventory or accounting side effects', function () {
    $context = proformaAuditContext();
    $purchase = createAuditProforma($context);

    $this->actingAs($context['admin'])
        ->post(route('compras.status', $purchase), ['status' => 'canceled'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($purchase->fresh()->status)->toBe('canceled')
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0)
        ->and(InventoryMovement::query()->where('reference', 'purchase:'.$purchase->id)->exists())->toBeFalse()
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->exists())->toBeFalse();
});

test('cash and transfer receipts enter stock and close the purchase', function () {
    $context = proformaAuditContext();

    foreach (['cash', 'transfer'] as $paymentType) {
        $purchase = createAuditProforma($context);

        $this->actingAs($context['admin'])
            ->post(route('compras.status', $purchase), [
                'status' => 'received',
                'payment_type' => $paymentType,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($purchase->fresh()->status)->toBe('completed')
            ->and($purchase->fresh()->payment_type)->toBe($paymentType)
            ->and(InventoryMovement::query()->where('reference', 'purchase:'.$purchase->id)->where('type', 'in')->count())->toBe(1)
            ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->where('status', JournalEntry::STATUS_POSTED)->count())->toBe(1);
    }

    expect((float) $context['product']->fresh()->stock)->toBe(4.0);
});

test('credit-limit failure rolls back receipt inventory accounting and status', function () {
    $context = proformaAuditContext();
    $context['supplier']->update(['credit_limit' => 10]);
    $purchase = createAuditProforma($context, [
        'items' => [[
            'product_id' => $context['product']->id,
            'unit_id' => $context['unit']->id,
            'quantity' => 2,
            'price' => 10,
        ]],
    ]);

    $this->actingAs($context['admin'])
        ->post(route('compras.status', $purchase), [
            'status' => 'received',
            'payment_type' => 'credit',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($purchase->fresh()->status)->toBe('ordered')
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0)
        ->and(InventoryMovement::query()->where('reference', 'purchase:'.$purchase->id)->exists())->toBeFalse()
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->exists())->toBeFalse();
});

test('invalid line unit rolls back the whole proforma', function () {
    $context = proformaAuditContext();
    $otherUnit = Unit::query()->create([
        'name' => 'Unidad ajena QA',
        'abbreviation' => 'uaq',
        'type' => 'unit',
        'is_active' => true,
    ]);

    $this->actingAs($context['admin'])
        ->from(route('compras.proformas.create'))
        ->post(route('compras.store'), [
            'supplier_id' => $context['supplier']->id,
            'warehouse_id' => $context['warehouse']->id,
            'date' => now()->toDateString(),
            'purchase_mode' => 'proforma',
            'currency' => 'NIO',
            'exchange_rate' => 1,
            'items' => [[
                'product_id' => $context['product']->id,
                'unit_id' => $otherUnit->id,
                'quantity' => 1,
                'price' => 10,
            ]],
        ])
        ->assertRedirect(route('compras.proformas.create'))
        ->assertSessionHas('error', 'Unidad de medida no configurada para este producto.');

    expect(Purchase::query()->count())->toBe(0)
        ->and(PurchaseDetail::query()->count())->toBe(0);
});

test('foreign-currency proforma preserves document totals and applies company cost on receipt', function () {
    $context = proformaAuditContext();
    $purchase = createAuditProforma($context, [
        'currency' => 'USD',
        'exchange_rate' => 36.5,
        'payment_type' => 'cash',
        'items' => [[
            'product_id' => $context['product']->id,
            'unit_id' => $context['unit']->id,
            'quantity' => 3,
            'price' => 10,
        ]],
    ]);
    $detail = $purchase->details()->firstOrFail();

    expect((float) $detail->subtotal)->toBe(30.0)
        ->and((float) $purchase->subtotal)->toBe(1095.0)
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0);

    $this->actingAs($context['admin'])->post(route('compras.status', $purchase), [
        'status' => 'received',
        'payment_type' => 'cash',
    ])->assertSessionHas('success');

    expect((float) $context['product']->fresh()->stock)->toBe(3.0)
        ->and((float) $context['product']->fresh()->purchase_price)->toBe(365.0);
});

test('invalid proforma fields are rejected without partial database writes', function () {
    $context = proformaAuditContext();

    $this->actingAs($context['admin'])
        ->from(route('compras.proformas.create'))
        ->post(route('compras.store'), [
            'supplier_id' => 999999,
            'warehouse_id' => 999999,
            'date' => 'not-a-date',
            'purchase_mode' => 'unknown',
            'payment_type' => 'barter',
            'currency' => 'BTC',
            'exchange_rate' => 0,
            'items' => [],
        ])
        ->assertRedirect(route('compras.proformas.create'))
        ->assertSessionHasErrors([
            'supplier_id',
            'warehouse_id',
            'date',
            'purchase_mode',
            'payment_type',
            'currency',
            'exchange_rate',
            'items',
        ]);

    expect(Purchase::query()->count())->toBe(0)
        ->and(PurchaseDetail::query()->count())->toBe(0)
        ->and(InventoryMovement::query()->count())->toBe(0);
});

test('purchase totals that exceed database precision are rejected without writes', function () {
    $context = proformaAuditContext();

    $this->actingAs($context['admin'])
        ->from(route('compras.proformas.create'))
        ->post(route('compras.store'), [
            'supplier_id' => $context['supplier']->id,
            'warehouse_id' => $context['warehouse']->id,
            'date' => now()->toDateString(),
            'purchase_mode' => 'proforma',
            'payment_type' => 'credit',
            'currency' => 'USD',
            'exchange_rate' => 40,
            'items' => [[
                'product_id' => $context['product']->id,
                'unit_id' => $context['unit']->id,
                'quantity' => 100,
                'price' => 50000,
            ]],
        ])
        ->assertRedirect(route('compras.proformas.create'))
        ->assertSessionHas('error');

    expect(Purchase::query()->count())->toBe(0)
        ->and(PurchaseDetail::query()->count())->toBe(0);
});

test('usd proformas reject a one-to-one fallback when no catalog rate exists', function () {
    $context = proformaAuditContext();
    $payload = [
        'supplier_id' => $context['supplier']->id,
        'warehouse_id' => $context['warehouse']->id,
        'date' => now()->toDateString(),
        'purchase_mode' => 'proforma',
        'payment_type' => 'credit',
        'currency' => 'USD',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $context['product']->id,
            'unit_id' => $context['unit']->id,
            'quantity' => 2,
            'price' => 10,
        ]],
    ];

    $this->actingAs($context['admin'])
        ->from(route('compras.proformas.create'))
        ->post(route('compras.store'), $payload)
        ->assertRedirect(route('compras.proformas.create'))
        ->assertSessionHas('error');

    expect(Purchase::query()->count())->toBe(0);
});

test('inactive products are rejected when the request bypasses the UI search', function () {
    $context = proformaAuditContext();
    $context['product']->update(['status' => 'inactive']);

    $this->actingAs($context['admin'])->post(route('compras.store'), [
        'supplier_id' => $context['supplier']->id,
        'warehouse_id' => $context['warehouse']->id,
        'date' => now()->toDateString(),
        'purchase_mode' => 'proforma',
        'payment_type' => 'credit',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $context['product']->id,
            'unit_id' => $context['unit']->id,
            'quantity' => 2,
            'price' => 10,
        ]],
    ])->assertSessionHasErrors('items.0.product_id');

    expect(Purchase::query()->count())->toBe(0);
});

test('purchase proforma mutations create audit-log records', function () {
    $context = proformaAuditContext();
    $purchase = createAuditProforma($context);

    $this->actingAs($context['admin'])->post(route('compras.status', $purchase), [
        'status' => 'received',
        'payment_type' => 'cash',
    ])->assertSessionHas('success');

    expect(AuditLog::query()->where('model_type', Purchase::class)->where('model_id', $purchase->id)->pluck('action')->all())
        ->toContain('purchase_proforma.created', 'purchase_proforma.received');
});

test('a stale proforma edit cannot return a received purchase to ordered status', function () {
    $context = proformaAuditContext();
    $purchase = createAuditProforma($context);

    $this->actingAs($context['admin'])->post(route('compras.status', $purchase), [
        'status' => 'received',
        'payment_type' => 'cash',
    ])->assertSessionHas('success');

    $this->actingAs($context['admin'])->put(route('compras.update', $purchase), [
        'supplier_id' => $context['supplier']->id,
        'warehouse_id' => $context['warehouse']->id,
        'date' => now()->toDateString(),
        'purchase_mode' => 'proforma',
        'payment_type' => 'credit',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $context['product']->id,
            'unit_id' => $context['unit']->id,
            'quantity' => 99,
            'price' => 1,
        ]],
    ])->assertSessionHas('error');

    expect($purchase->fresh()->status)->toBe('completed')
        ->and((float) $purchase->details()->firstOrFail()->quantity)->toBe(2.0);
});

test('an open proforma does not change the supplier master cost before receipt', function () {
    $context = proformaAuditContext();
    $context['product']->suppliers()->attach($context['supplier']->id, ['purchase_price' => 7.5]);

    createAuditProforma($context, [
        'items' => [[
            'product_id' => $context['product']->id,
            'unit_id' => $context['unit']->id,
            'quantity' => 2,
            'price' => 99,
        ]],
    ]);

    expect((float) $context['product']->suppliers()->firstOrFail()->pivot->purchase_price)->toBe(7.5)
        ->and((float) $context['product']->fresh()->purchase_price)->toBe(10.0);
});

test('the purchases list hides mutation controls from a view-only user', function () {
    $context = proformaAuditContext();
    $purchase = createAuditProforma($context);
    $viewerRole = Role::query()->create([
        'name' => 'Solo lectura QA',
        'slug' => 'qa-read-only-controls',
        'is_system' => false,
    ]);
    $viewerRole->permissions()->sync([
        Permission::query()->where('slug', 'compras.view')->firstOrFail()->id,
    ]);
    Module::query()->where('slug', 'compras')->firstOrFail()->roles()->syncWithoutDetaching([$viewerRole->id]);
    $viewer = User::factory()->create([
        'role' => 'qa-read-only-controls',
        'is_active' => true,
        'force_password_change' => false,
    ]);
    $viewer->roles()->sync([$viewerRole->id]);

    $this->actingAs($viewer)
        ->get(route('compras.index'))
        ->assertOk()
        ->assertDontSee(route('compras.proformas.create'), false)
        ->assertDontSee(route('compras.edit', $purchase), false)
        ->assertDontSee('¿Eliminar esta compra?')
        ->assertDontSee('>Eliminar</button>', false);
});

test('supplier and product labels are escaped in proforma output', function () {
    $context = proformaAuditContext();
    $context['supplier']->update(['name' => '<script>supplierAttack()</script>']);
    $context['product']->update(['name' => '<img src=x onerror=productAttack()>']);
    $purchase = createAuditProforma($context);

    $this->actingAs($context['admin'])
        ->get(route('compras.show', $purchase))
        ->assertOk()
        ->assertDontSee('<script>supplierAttack()</script>', false)
        ->assertDontSee('<img src=x onerror=productAttack()>', false)
        ->assertSee('&lt;script&gt;supplierAttack()&lt;/script&gt;', false)
        ->assertSee('&lt;img src=x onerror=productAttack()&gt;', false);
});

test('supplier-scoped product search remains responsive with a large catalog', function () {
    $context = proformaAuditContext();
    $now = now();

    foreach (array_chunk(range(1, 2500), 250) as $chunk) {
        Product::query()->insert(array_map(fn (int $number) => [
            'category_id' => $context['category']->id,
            'name' => 'Producto rendimiento '.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'code' => 'QA-PERF-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'purchase_price' => 10,
            'sale_price' => 15,
            'stock' => 0,
            'unit' => 'und',
            'base_unit_id' => $context['unit']->id,
            'status' => 'active',
            'low_stock_threshold' => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ], $chunk));
    }

    Product::query()
        ->where('code', 'like', 'QA-PERF-%')
        ->orderBy('id')
        ->pluck('id')
        ->chunk(250)
        ->each(function ($ids) use ($context, $now): void {
            DB::table('product_supplier')->insert($ids->map(fn (int $productId) => [
                'product_id' => $productId,
                'supplier_id' => $context['supplier']->id,
                'purchase_price' => 10,
                'supplier_code' => null,
                'preferred' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

    $started = hrtime(true);
    $response = $this->actingAs($context['admin'])->getJson(route('compras.products.search', [
        'supplier_id' => $context['supplier']->id,
        'search' => 'QA-PERF-2500',
    ]));
    $elapsedMs = (hrtime(true) - $started) / 1_000_000;

    fwrite(STDOUT, sprintf("\nQA product search (2,500 linked products): %.2f ms\n", $elapsedMs));

    $response->assertOk()->assertJsonCount(1);
    expect($elapsedMs)->toBeLessThan(2000.0);
});
