<?php

use App\Models\Arqueo;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\JournalEntry;
use App\Models\Module;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CreditService;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function financialIntegrityAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([$role->id]);
    openCashSessionFor($user);

    return $user;
}

test('credit purchases enter inventory and canceled purchases reverse stock and accounting', function () {
    $admin = financialIntegrityAdmin();
    $this->seed(InventoryCatalogSeeder::class);

    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();
    $supplier = Supplier::query()->create(['name' => 'Proveedor integridad', 'status' => 'active']);
    $category = Category::firstOrCreate(['name' => 'Integridad']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto compra pendiente',
        'code' => 'INT-COMPRA-1',
        'purchase_price' => 25,
        'sale_price' => 40,
        'stock' => 0,
        'unit' => 'und',
        'base_unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $payload = [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'currency' => 'NIO',
        'exchange_rate' => 1,
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 3,
            'price' => 25,
        ]],
    ];

    $this->actingAs($admin)->post(route('compras.store'), $payload)
        ->assertRedirect(route('compras.index'));

    $purchase = Purchase::query()->firstOrFail();
    expect((float) $product->fresh()->stock)->toBe(3.0)
        ->and($purchase->status)->toBe('pending')
        ->and(JournalEntry::query()->where('source_type', Purchase::class)->where('source_id', $purchase->id)->where('status', JournalEntry::STATUS_POSTED)->exists())->toBeTrue();

    $payload['status'] = 'canceled';
    $this->actingAs($admin)->put(route('compras.update', $purchase), $payload)
        ->assertRedirect(route('compras.index'));

    expect((float) $product->fresh()->stock)->toBe(0.0)
        ->and(JournalEntry::query()
            ->where('source_type', Purchase::class)
            ->where('source_id', $purchase->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->exists())->toBeFalse();
});

test('repair order stores the discounted total and correct balance', function () {
    $admin = financialIntegrityAdmin();
    Module::query()->where('slug', 'reparaciones')->update(['is_active' => true]);

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        'client_name' => 'Cliente descuento',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone',
        'problem_description' => 'Pantalla dañada',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'labor_cost' => 100,
        'discount_percentage' => 10,
        'advance_payment' => 20,
        'payment_type' => 'cash',
    ])->assertRedirect();

    $order = RepairOrder::query()->latest('id')->firstOrFail();

    expect((float) $order->total)->toBe(90.0)
        ->and($order->balance())->toBe(70.0)
        ->and($order->payment_status)->toBe('partial');
});

test('repair order stores one optimized photo', function () {
    Storage::fake('public');
    $admin = financialIntegrityAdmin();
    Module::query()->where('slug', 'reparaciones')->update(['is_active' => true]);

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        'client_name' => 'Cliente con fotos',
        'device_brand' => 'Anillo',
        'device_model' => 'Oro 14K',
        'problem_description' => 'Pulido',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'payment_type' => 'cash',
        'photo' => UploadedFile::fake()->image('joya.jpg', 1800, 1200),
    ])->assertRedirect();

    $order = RepairOrder::query()->latest('id')->firstOrFail();
    expect($order->photos)->toHaveCount(1)
        ->and($order->photos->first()->path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($order->photos->first()->path);
    $this->get($order->photos->first()->url)->assertOk();
});

test('cash repair payments are posted and included in the cash closing', function () {
    $admin = financialIntegrityAdmin();
    Module::query()->where('slug', 'reparaciones')->update(['is_active' => true]);

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        'client_name' => 'Cliente taller',
        'device_brand' => 'Anillo',
        'device_model' => 'Oro 14K',
        'problem_description' => 'Ajustar talla',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'labor_cost' => 100,
        'advance_payment' => 40,
        'payment_type' => 'cash',
    ])->assertRedirect();

    $order = RepairOrder::query()->latest('id')->firstOrFail();
    expect($order->caja_session_id)->not->toBeNull()
        ->and(JournalEntry::query()->where('source_type', RepairOrder::class)->where('source_id', $order->id)->where('status', JournalEntry::STATUS_POSTED)->exists())->toBeTrue();

    $this->actingAs($admin)->post(route('arqueo.run'), [
        'date' => now()->toDateString(),
        'caja_session_id' => $order->caja_session_id,
        'physical_counts' => [['amount' => 10, 'qty' => 4]],
    ])->assertOk();

    expect((float) Arqueo::query()->latest('id')->value('cash_total'))->toBe(40.0);
});

test('delivering a repair collects its outstanding balance', function () {
    $admin = financialIntegrityAdmin();
    Module::query()->where('slug', 'reparaciones')->update(['is_active' => true]);
    $order = RepairOrder::query()->create([
        'order_number' => 'REP-ENTREGA-001',
        'client_name' => 'Cliente entrega',
        'device_brand' => 'Anillo',
        'device_model' => 'Oro',
        'problem_description' => 'Pulido',
        'status' => 'ready',
        'priority' => 'normal',
        'user_id' => $admin->id,
        'received_date' => now()->toDateString(),
        'labor_cost' => 600,
        'total' => 600,
        'payment_type' => 'cash',
        'payment_status' => 'pending',
    ]);

    $this->actingAs($admin)->patch(route('reparaciones.status', $order), ['status' => 'delivered'])
        ->assertRedirect();

    expect((float) $order->fresh()->advance_payment)->toBe(600.0)
        ->and($order->fresh()->payment_status)->toBe('paid')
        ->and(JournalEntry::query()->where('source_type', RepairOrder::class)->where('source_id', $order->id)->where('status', JournalEntry::STATUS_POSTED)->exists())->toBeTrue();

    $invoice = Sale::query()->where('repair_order_id', $order->id)->firstOrFail();
    expect($invoice->status)->toBe('completed')
        ->and((float) $invoice->total)->toBe(600.0)
        ->and($invoice->notes)->toContain('Taller de reparación');
});

test('canceling a workshop invoice reverses the repair payment and accounting entry', function () {
    $admin = financialIntegrityAdmin();
    Module::query()->where('slug', 'reparaciones')->update(['is_active' => true]);
    $order = RepairOrder::query()->create([
        'order_number' => 'REP-ANULA-001',
        'client_name' => 'Cliente anulación',
        'device_brand' => 'Cadena',
        'device_model' => 'Plata',
        'problem_description' => 'Soldadura',
        'status' => 'ready',
        'priority' => 'normal',
        'user_id' => $admin->id,
        'received_date' => now()->toDateString(),
        'labor_cost' => 450,
        'total' => 450,
        'payment_type' => 'cash',
        'payment_status' => 'pending',
    ]);

    $this->actingAs($admin)->patch(route('reparaciones.status', $order), ['status' => 'delivered'])
        ->assertRedirect();
    $invoice = Sale::query()->where('repair_order_id', $order->id)->firstOrFail();

    $this->actingAs($admin)->delete(route('facturacion.destroy', $invoice))
        ->assertRedirect(route('facturacion.index'));

    expect($invoice->fresh()->status)->toBe('canceled')
        ->and($order->fresh()->status)->toBe('ready')
        ->and((float) $order->fresh()->advance_payment)->toBe(0.0)
        ->and($order->fresh()->payment_status)->toBe('pending')
        ->and($order->fresh()->payment_received_at)->toBeNull()
        ->and(JournalEntry::query()->where('source_type', RepairOrder::class)->where('source_id', $order->id)->where('status', JournalEntry::STATUS_POSTED)->exists())->toBeFalse();
});

test('repair order rejects simultaneous fixed and percentage discounts', function () {
    $admin = financialIntegrityAdmin();
    Module::query()->where('slug', 'reparaciones')->update(['is_active' => true]);

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        'client_name' => 'Cliente descuento inválido',
        'device_brand' => 'Cadena',
        'device_model' => 'Plata 925',
        'problem_description' => 'Soldar eslabón',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'labor_cost' => 100,
        'discount_type' => 'percentage',
        'discount_percentage' => 10,
        'discount_amount' => 5,
        'payment_type' => 'cash',
    ])->assertSessionHasErrors('discount_type');

    $this->assertDatabaseMissing('repair_orders', ['client_name' => 'Cliente descuento inválido']);
});

test('branch profitability counts an invoice once when it has multiple details', function () {
    $admin = financialIntegrityAdmin();
    $branch = Branch::query()->create([
        'code' => 'RENT-01',
        'name' => 'Sucursal Rentabilidad',
        'type' => 'sucursal',
        'is_active' => true,
    ]);
    $client = Client::query()->create(['name' => 'Cliente rentabilidad']);
    $category = Category::firstOrCreate(['name' => 'Rentabilidad']);
    $firstProduct = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto rentable A',
        'code' => 'RENT-A',
        'purchase_price' => 20,
        'sale_price' => 50,
        'stock' => 10,
        'unit' => 'unidad',
        'status' => 'active',
    ]);
    $secondProduct = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto rentable B',
        'code' => 'RENT-B',
        'purchase_price' => 30,
        'sale_price' => 50,
        'stock' => 10,
        'unit' => 'unidad',
        'status' => 'active',
    ]);
    $sale = Sale::query()->create([
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'branch_id' => $branch->id,
        'date' => now()->toDateString(),
        'total' => 100,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);
    DB::table('sales')->where('id', $sale->id)->update(['date' => now()->toDateString()]);

    SaleDetail::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $firstProduct->id,
        'quantity' => 1,
        'base_quantity' => 1,
        'price' => 50,
        'subtotal' => 50,
    ]);
    SaleDetail::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $secondProduct->id,
        'quantity' => 1,
        'base_quantity' => 1,
        'price' => 50,
        'subtotal' => 50,
    ]);

    $this->actingAs($admin)
        ->get(route('reportes.index', [
            'report_type' => 'profit',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('summary', function (array $summary) use ($branch): bool {
            $row = $summary['by_branch']->firstWhere('branch_id', $branch->id);

            return (float) $row->total_sales === 100.0
                && (float) $row->total_cost === 50.0
                && (float) $row->gross_profit === 50.0;
        });
});

test('credit payments cannot exceed debt and aging uses the outstanding balance', function () {
    $admin = financialIntegrityAdmin();
    $client = Client::query()->create([
        'name' => 'Cliente crédito integridad',
        'phone' => '88880000',
        'credit_enabled' => true,
        'credit_limit' => 1000,
    ]);
    Sale::query()->create([
        'invoice_number' => 'CRED-INT-001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'date' => now()->subDays(60),
        'due_date' => now()->subDays(40),
        'payment_type' => 'credit',
        'status' => 'pending',
        'tax_included' => false,
        'tax_rate' => 0,
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
    ]);

    CreditPayment::query()->create([
        'client_id' => $client->id,
        'amount' => 30,
        'payment_type' => 'cash',
        'payment_date' => now(),
        'user_id' => $admin->id,
    ]);

    $credit = app(CreditService::class);
    expect($credit->pendingDebt($client))->toBe(70.0)
        ->and($credit->agingReport()['days_31_60'])->toBe(70.0)
        ->and($credit->portfolioSummary()['overdue_total'])->toBe(70.0);

    $this->actingAs($admin)
        ->from(route('creditos.create', $client))
        ->post(route('creditos.store'), [
            'client_id' => $client->id,
            'amount' => 70.01,
            'payment_type' => 'cash',
            'request_token' => (string) Str::uuid(),
        ])
        ->assertRedirect(route('creditos.create', $client))
        ->assertSessionHas('error');

    expect((float) CreditPayment::query()->sum('amount'))->toBe(30.0);
});

test('credit payments are idempotent and cash payments belong to the open register', function () {
    $admin = financialIntegrityAdmin();
    $client = Client::query()->create([
        'name' => 'Cliente abono idempotente',
        'credit_enabled' => true,
        'credit_limit' => 500,
    ]);
    Sale::query()->create([
        'invoice_number' => 'CRED-IDEMP-001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'date' => now(),
        'payment_type' => 'credit',
        'status' => 'pending',
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
    ]);
    $token = (string) Str::uuid();
    $payload = [
        'client_id' => $client->id,
        'amount' => 25,
        'payment_type' => 'cash',
        'request_token' => $token,
    ];

    $this->actingAs($admin)->post(route('creditos.store'), $payload)->assertSessionHas('success');
    $this->actingAs($admin)->post(route('creditos.store'), $payload)
        ->assertSessionHas('success', 'El abono ya había sido registrado; no se duplicó.');

    $payment = CreditPayment::query()->where('request_token', $token)->firstOrFail();
    expect(CreditPayment::query()->where('request_token', $token)->count())->toBe(1)
        ->and($payment->caja_session_id)->not->toBeNull();
});
