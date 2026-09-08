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

test('purchase and proforma use separate entry buttons without a record type selector', function () {
    $context = purchaseProformaContext();

    $this->actingAs($context['admin'])
        ->get(route('compras.index'))
        ->assertOk()
        ->assertSee('Proforma compras')
        ->assertSee('+ Nueva compra');

    $this->actingAs($context['admin'])
        ->get(route('compras.create'))
        ->assertOk()
        ->assertSee('Ingreso a inventario')
        ->assertDontSee('Tipo de registro');

    $this->actingAs($context['admin'])
        ->get(route('compras.proformas.create'))
        ->assertOk()
        ->assertSee('Proforma de compra')
        ->assertSee('pedido en proceso')
        ->assertDontSee('Tipo de registro');
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
