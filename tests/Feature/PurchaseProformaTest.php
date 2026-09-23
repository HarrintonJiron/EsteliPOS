<?php

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
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

uses(RefreshDatabase::class);

function purchaseProformaContext(): array
{
    test()->seed(ConfigurationSeeder::class);
    test()->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplier = Supplier::query()->create(['name' => 'Proveedor pedido', 'status' => 'active']);
    $category = Category::firstOrCreate(['name' => 'Pedidos']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto por recibir',
        'code' => 'PEDIDO-001',
        'purchase_price' => 10,
        'sale_price' => 15,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    return compact('admin', 'unit', 'warehouse', 'supplier', 'product');
}

test('purchases and proformas share one entry button and one form that asks if the merchandise arrived', function () {
    $context = purchaseProformaContext();

    $this->actingAs($context['admin'])
        ->get(route('compras.index'))
        ->assertOk()
        ->assertSee('+ Nueva compra')
        ->assertDontSee('Proforma compras')
        ->assertDontSee(route('compras.proformas.create'), false);

    $this->actingAs($context['admin'])
        ->get(route('compras.create'))
        ->assertOk()
        ->assertSee('¿La mercadería ya llegó?')
        ->assertSee('Sí, ya llegó')
        ->assertSee('Aún no · pedido')
        ->assertSee('Ingreso a inventario')
        ->assertSee('const selectedProductIds = new Set(items.map(item => String(item.id)));', false)
        ->assertSee('Los productos encontrados ya están agregados a la compra.')
        ->assertSee('searchInput.blur();', false)
        ->assertSee('name="purchase_mode" id="purchase_mode" value="immediate"', false)
        ->assertDontSee('Tipo de registro');
});

test('the shared purchase form can open already set as an order and keeps the old proforma link working', function () {
    $context = purchaseProformaContext();

    foreach ([route('compras.create', ['modo' => 'pedido']), route('compras.proformas.create')] as $url) {
        $this->actingAs($context['admin'])
            ->get($url)
            ->assertOk()
            ->assertSee('¿La mercadería ya llegó?')
            ->assertSee('Proforma de compra')
            ->assertSee('pedido en proceso')
            ->assertSee('name="purchase_mode" id="purchase_mode" value="proforma"', false)
            ->assertDontSee('Tipo de registro');
    }
});

test('the arrival question sits in positioned labels so opening it cannot scroll the whole app', function () {
    $context = purchaseProformaContext();

    $html = $this->actingAs($context['admin'])->get(route('compras.create'))->assertOk()->getContent();

    expect($html)->toContain('<label class="relative cursor-pointer">')
        ->and(substr_count($html, 'name="purchase_mode_choice" value='))->toBe(2)
        ->and($html)->toContain('applyPurchaseMode');
});

test('editing a purchase or a proforma does not offer to switch its type', function () {
    $context = purchaseProformaContext();

    foreach (['immediate' => 'completed', 'proforma' => 'ordered'] as $mode => $status) {
        $this->actingAs($context['admin'])->post(route('compras.store'), [
            'supplier_id' => $context['supplier']->id,
            'warehouse_id' => $context['warehouse']->id,
            'date' => now()->toDateString(),
            'payment_type' => 'transfer',
            'purchase_mode' => $mode,
            'items' => [['product_id' => $context['product']->id, 'unit_id' => $context['unit']->id, 'quantity' => 1, 'price' => 5]],
        ])->assertSessionHasNoErrors();

        $purchase = Purchase::query()->latest('id')->firstOrFail();
        expect($purchase->status)->toBe($status);

        $this->actingAs($context['admin'])->get(route('compras.edit', $purchase->id))
            ->assertOk()
            ->assertDontSee('id="purchaseModeChooser"', false);
    }
});

test('purchases receive unique persisted document numbers from the configured sequence', function () {
    $context = purchaseProformaContext();

    foreach ([10, 20] as $price) {
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
                'quantity' => 1,
                'price' => $price,
            ]],
        ])->assertRedirect(route('compras.index'));
    }

    expect(Purchase::query()->orderBy('id')->pluck('document_number')->all())
        ->toBe(['COM-000001', 'COM-000002']);
});

test('a purchase proforma stays in process without inventory or accounting effects', function () {
    $context = purchaseProformaContext();

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
            'quantity' => 5,
            'price' => 10,
        ]],
    ])->assertRedirect(route('compras.index'))
        ->assertSessionHas('success');

    $purchase = Purchase::query()->firstOrFail();

    expect($purchase->status)->toBe('ordered')
        ->and($purchase->statusLabel())->toBe('Pedido en proceso')
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0)
        ->and(InventoryMovement::query()->where('reference', 'purchase:'.$purchase->id)->exists())->toBeFalse()
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->exists())->toBeFalse();

    $this->actingAs($context['admin'])
        ->get(route('compras.show', $purchase->id))
        ->assertOk()
        ->assertSee('Pedido en proceso')
        ->assertSee('Imprimir ticket')
        ->assertSee('Imprimir PDF')
        ->assertSee('Confirmar ingreso y forma de pago')
        ->assertSee('Confirmar ingreso')
        ->assertSee('Eliminar producto')
        ->assertSee('Sin movimientos de inventario o contabilidad');

    $this->actingAs($context['admin'])
        ->get(route('compras.proformas.ticket', $purchase->id))
        ->assertOk()
        ->assertSee('PROFORMA DE COMPRA')
        ->assertSee('Imprimir ticket 80 mm');

    $this->actingAs($context['admin'])
        ->get(route('compras.proformas.pdf', $purchase->id))
        ->assertOk()
        ->assertSee('PROFORMA DE COMPRA')
        ->assertSee('Imprimir / Guardar PDF');
});

test('a product can be removed from an open purchase proforma and totals are recalculated', function () {
    $context = purchaseProformaContext();
    $secondProduct = Product::query()->create([
        'category_id' => $context['product']->category_id,
        'name' => 'Producto que permanece',
        'code' => 'PEDIDO-002',
        'purchase_price' => 20,
        'sale_price' => 30,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $context['unit']->id,
        'status' => 'active',
    ]);

    $this->actingAs($context['admin'])->post(route('compras.store'), [
        'supplier_id' => $context['supplier']->id,
        'warehouse_id' => $context['warehouse']->id,
        'date' => now()->toDateString(),
        'purchase_mode' => 'proforma',
        'payment_type' => 'credit',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [
            [
                'product_id' => $context['product']->id,
                'unit_id' => $context['unit']->id,
                'quantity' => 5,
                'price' => 10,
            ],
            [
                'product_id' => $secondProduct->id,
                'unit_id' => $context['unit']->id,
                'quantity' => 2,
                'price' => 20,
            ],
        ],
    ])->assertRedirect(route('compras.index'));

    $purchase = Purchase::query()->firstOrFail();
    $removedDetail = PurchaseDetail::query()->where('purchase_id', $purchase->id)->where('product_id', $context['product']->id)->firstOrFail();
    $remainingDetail = PurchaseDetail::query()->where('purchase_id', $purchase->id)->where('product_id', $secondProduct->id)->firstOrFail();

    $this->actingAs($context['admin'])
        ->from(route('compras.show', $purchase->id))
        ->delete(route('compras.proformas.details.destroy', [$purchase->id, $removedDetail->id]))
        ->assertRedirect(route('compras.show', $purchase->id))
        ->assertSessionHas('success');

    $purchase->refresh();

    expect(PurchaseDetail::query()->whereKey($removedDetail->id)->exists())->toBeFalse()
        ->and($purchase->details()->count())->toBe(1)
        ->and((float) $purchase->subtotal)->toBe((float) $remainingDetail->subtotal)
        ->and((float) $purchase->tax_total)->toBe((float) $remainingDetail->tax_amount)
        ->and((float) $purchase->total)->toBe(round((float) $remainingDetail->subtotal + (float) $remainingDetail->tax_amount, 2))
        ->and((float) $context['product']->fresh()->stock)->toBe(0.0)
        ->and((float) $secondProduct->fresh()->stock)->toBe(0.0)
        ->and(InventoryMovement::query()->where('reference', 'purchase:'.$purchase->id)->exists())->toBeFalse()
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->exists())->toBeFalse();
});

test('an open purchase proforma cannot be left without products', function () {
    $context = purchaseProformaContext();

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
            'quantity' => 5,
            'price' => 10,
        ]],
    ]);

    $purchase = Purchase::query()->firstOrFail();
    $detail = $purchase->details()->firstOrFail();

    $this->actingAs($context['admin'])
        ->delete(route('compras.proformas.details.destroy', [$purchase->id, $detail->id]))
        ->assertSessionHas('error', 'La proforma debe conservar al menos un producto. Puedes anularla si ya no la necesitas.');

    expect($purchase->details()->count())->toBe(1);
});

test('confirming a proforma receives stock once and creates the purchase accounting entry', function () {
    $context = purchaseProformaContext();

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
            'quantity' => 5,
            'price' => 10,
        ]],
    ])->assertRedirect(route('compras.index'));

    $purchase = Purchase::query()->firstOrFail();

    $this->actingAs($context['admin'])
        ->from(route('compras.show', $purchase->id))
        ->post(route('compras.status', $purchase->id), [
            'status' => 'received',
            'payment_type' => 'credit',
        ])
        ->assertRedirect(route('compras.show', $purchase->id))
        ->assertSessionHas('success');

    expect($purchase->fresh()->status)->toBe('pending')
        ->and((float) $context['product']->fresh()->stock)->toBe(5.0)
        ->and(InventoryMovement::query()->where('reference', 'purchase:'.$purchase->id)->count())->toBe(1)
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->where('status', JournalEntry::STATUS_POSTED)->exists())->toBeTrue();

    $this->actingAs($context['admin'])
        ->post(route('compras.status', $purchase->id), [
            'status' => 'received',
            'payment_type' => 'credit',
        ])
        ->assertSessionHas('error');

    expect((float) $context['product']->fresh()->stock)->toBe(5.0)
        ->and(InventoryMovement::query()->where('reference', 'purchase:'.$purchase->id)->count())->toBe(1);
});
