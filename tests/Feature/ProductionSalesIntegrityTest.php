<?php

use App\Models\AuditLog;
use App\Models\CajaSession;
use App\Models\Category;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CreditOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function productionSalesUser(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    openCashSessionFor($user);

    return $user;
}

function productionSalesProduct(int $stock = 10, float $price = 100): Product
{
    $category = Category::firstOrCreate(['name' => 'Integridad']);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Producto controlado',
        'code' => 'SAFE-'.str()->random(8),
        'purchase_price' => 50,
        'sale_price' => $price,
        'stock' => $stock,
        'unit' => 'unidad',
        'status' => 'active',
    ]);
}

function posPayload(Product $product, array $overrides = []): array
{
    return array_merge([
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'discount' => 0]]),
        'amount_received' => 500,
        'request_token' => (string) Str::uuid(),
    ], $overrides);
}

beforeEach(function () {
    Tax::query()->update(['is_default' => false]);
    Tax::firstOrCreate(
        ['code' => 'EXENTO-INTEGRIDAD'],
        ['name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true],
    )->update(['is_default' => true, 'is_active' => true]);
});

test('the pos rejects the same product presentation twice in the ticket', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct();

    $this->actingAs($user)->from(route('facturacion.pos'))->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([
            ['product_id' => $product->id, 'quantity' => 1, 'discount' => 0],
            ['product_id' => $product->id, 'quantity' => 2, 'discount' => 0],
        ]),
        'amount_received' => 500,
    ])->assertRedirect(route('facturacion.pos'))
        ->assertSessionHasErrors('items.1.unit_id');

    expect(Sale::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('cash sales require an open cash register', function () {
    $user = productionSalesUser();
    CajaSession::query()->where('opened_by', $user->id)
        ->update(['status' => 'closed', 'open_guard' => null, 'closed_at' => now()]);
    $product = productionSalesProduct();

    $this->actingAs($user)
        ->from(route('facturacion.pos'))
        ->post(route('facturacion.pos-store'), posPayload($product))
        ->assertRedirect(route('facturacion.pos'))
        ->assertSessionHas('error', 'Debes abrir una caja antes de registrar una venta en efectivo.');

    expect(Sale::query()->count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('receipt displays change amount from the persisted sale record', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct(stock: 10, price: 100);
    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 1,
            'discount' => 0,
        ]]),
        'amount_received' => 150,
    ])->assertRedirect();

    $sale = Sale::latest('id')->firstOrFail();

    expect((float) $sale->change_amount)->toBe(50.0);

    $this->actingAs($user)->get(route('facturacion.receipt', $sale->id))
        ->assertOk()
        ->assertSee('CAMBIO', false)
        ->assertSee('50.00', false);
});

test('the pos uses the server price and ignores a manipulated browser price', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct(stock: 10, price: 125);
    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 0.01,
            'discount' => 0,
        ]]),
        'amount_received' => 125,
    ])->assertRedirect();

    $sale = Sale::latest('id')->firstOrFail();
    expect((float) $sale->total)->toBe(125.0)
        ->and((float) $sale->details()->firstOrFail()->price)->toBe(125.0);
});

test('the pos rejects insufficient stock and rolls back the entire sale', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct(stock: 2);
    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldNotReceive('recordSale');
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($user)->from(route('facturacion.pos'))->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 3,
            'discount' => 0,
        ]]),
        'amount_received' => 500,
    ])->assertRedirect(route('facturacion.pos'))->assertSessionHasErrors('items');

    expect(Sale::count())->toBe(0)
        ->and(InventoryMovement::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(2);
});

test('the pos rejects invalid quantities and insufficient cash without side effects', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct();

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => -1]]),
        'amount_received' => 100,
    ])->assertSessionHasErrors('items.0.quantity');

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1]]),
        'amount_received' => 10,
    ])->assertSessionHasErrors('items');

    expect(Sale::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('credit sales require an enabled client and respect its limit', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct(price: 150);
    $client = Client::create([
        'name' => 'Cliente sin cupo',
        'code' => 'CRED-1',
        'credit_enabled' => true,
        'credit_limit' => 100,
        'credit_days' => 30,
    ]);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'credit',
        'client_id' => $client->id,
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1]]),
    ])->assertSessionHasErrors('items');

    expect(Sale::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('a one-time administrator override permits and audits an over-limit credit sale', function () {
    config(['cache.default' => 'array']);
    $cashier = productionSalesUser();
    $administrator = productionSalesUser();
    $product = productionSalesProduct(price: 150);
    $client = Client::create([
        'name' => 'Cliente autorizado sobre límite',
        'code' => 'CRED-OVERRIDE',
        'credit_enabled' => true,
        'credit_limit' => 100,
        'credit_days' => 30,
    ]);
    $token = app(CreditOverrideService::class)->issue($cashier, $administrator, $client, 150);
    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($cashier)->post(route('facturacion.pos-store'), [
        'payment_type' => 'credit',
        'client_id' => $client->id,
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1]]),
        'credit_override_token' => $token,
    ])->assertRedirect();

    expect(Sale::query()->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(9)
        ->and(AuditLog::query()->where('action', 'credit.override.used')->where('user_id', $administrator->id)->exists())->toBeTrue();
});

test('number sequences produce unique consecutive document numbers', function () {
    expect(NumberSequence::getNext('factura'))->toBe('FAC-000001')
        ->and(NumberSequence::getNext('factura'))->toBe('FAC-000002')
        ->and(NumberSequence::where('type', 'factura')->value('current_number'))->toBe(3);
});

test('the pos caps its initial catalog and can search products beyond that limit', function () {
    $user = productionSalesUser();
    $category = Category::firstOrCreate(['name' => 'Catálogo grande']);
    $now = now();
    $rows = [];
    for ($index = 1; $index <= 5000; $index++) {
        $rows[] = [
            'category_id' => $category->id,
            'name' => 'Producto volumen '.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            'code' => 'VOL-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            'purchase_price' => 5,
            'sale_price' => 10,
            'stock' => 100,
            'unit' => 'unidad',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    foreach (array_chunk($rows, 250) as $chunk) {
        Product::insert($chunk);
    }

    $this->actingAs($user)->get(route('facturacion.pos'))
        ->assertOk()
        ->assertViewHas('products', fn ($products) => $products->count() === 300);

    $this->actingAs($user)->getJson(route('facturacion.pos-products', ['search' => 'VOL-5000']))
        ->assertOk()
        ->assertJsonPath('0.code', 'VOL-5000');
});

test('repeating the same pos request does not duplicate the sale or inventory exit', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct();
    $payload = posPayload($product);
    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($user)->post(route('facturacion.pos-store'), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('facturacion.pos-store'), $payload)
        ->assertRedirect()
        ->assertSessionHas('success', 'La venta ya había sido procesada; no se duplicó.');

    expect(Sale::query()->count())->toBe(1)
        ->and(InventoryMovement::query()->where('type', 'out')->count())->toBe(1)
        ->and((float) $product->fresh()->stock)->toBe(9.0);
});

test('the pos rejects inactive products submitted outside the catalog', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct();
    $product->update(['status' => 'inactive']);

    $this->actingAs($user)->post(route('facturacion.pos-store'), posPayload($product))
        ->assertSessionHasErrors('items.0.product_id');

    expect(Sale::query()->count())->toBe(0)
        ->and((float) $product->fresh()->stock)->toBe(10.0);
});

test('fractional tax rounding keeps the sale details and accounting entry balanced', function () {
    $user = productionSalesUser();
    $product = productionSalesProduct(price: 40.50);
    Tax::query()->update(['is_default' => false]);
    Tax::query()->create([
        'code' => 'IVA-ROUNDING-QA',
        'name' => 'IVA 15% QA',
        'rate' => 0.15,
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('facturacion.pos-store'), posPayload($product, [
        'amount_received' => 100,
    ]))->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->latest('id')->firstOrFail();
    $entry = JournalEntry::query()->where('source_type', Sale::class)->where('source_id', $sale->id)->firstOrFail();

    expect((float) $sale->subtotal)->toBe(40.5)
        ->and((float) $sale->tax_total)->toBe(6.08)
        ->and((float) $sale->total)->toBe(46.58)
        ->and((float) $entry->total_debit)->toBe(46.58)
        ->and((float) $entry->total_credit)->toBe(46.58);
});
