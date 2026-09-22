<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\InventoryMovement;
use App\Models\PhoneTradeIn;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function tradeInTestUser(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    openCashSessionFor($user);

    return $user;
}

function tradeInTestProduct(array $overrides = []): Product
{
    $category = Category::firstOrCreate(['name' => 'Accesorios TradeIn']);

    return Product::create(array_merge([
        'category_id' => $category->id,
        'name' => 'Cargador USB-C',
        'code' => 'ACC-'.str()->random(8),
        'purchase_price' => 5,
        'sale_price' => 300,
        'stock' => 10,
        'unit' => 'unidad',
        'status' => 'active',
    ], $overrides));
}

beforeEach(function () {
    Tax::query()->update(['is_default' => false]);
    Tax::firstOrCreate(
        ['code' => 'EXENTO-TRADEIN'],
        ['name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true],
    )->update(['is_default' => true, 'is_active' => true]);
});

test('a fresh imei trade-in creates a new used-phone product and reduces the amount due', function () {
    $user = tradeInTestUser();
    $product = tradeInTestProduct();
    $category = Category::firstOrCreate(['name' => 'Telefonos TradeIn']);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'discount' => 0]]),
        'trade_ins' => json_encode([[
            'imei' => 'NEW-IMEI-100',
            'name' => 'iPhone 11 64GB',
            'brand' => 'Apple',
            'model' => '11',
            'color' => 'Negro',
            'battery_percentage' => 87,
            'category_id' => $category->id,
            'trade_in_value' => 100,
            'sale_price' => 210,
        ]]),
        'amount_received' => 200,
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->latest('id')->firstOrFail();
    expect((float) $sale->total)->toBe(300.0)
        ->and((float) $sale->trade_in_value)->toBe(100.0)
        ->and((float) $sale->change_amount)->toBe(0.0);

    $tradeIn = PhoneTradeIn::query()->where('sale_id', $sale->id)->firstOrFail();
    expect((float) $tradeIn->trade_in_value)->toBe(100.0)
        ->and($tradeIn->was_returning_phone)->toBeFalse();

    $newProduct = Product::query()->where('imei', 'NEW-IMEI-100')->firstOrFail();
    expect($newProduct->id)->toBe($tradeIn->product_id)
        ->and($newProduct->condition)->toBe('used')
        ->and($newProduct->brand)->toBe('Apple')
        ->and($newProduct->battery_percentage)->toBe(87)
        ->and((float) $newProduct->sale_price)->toBe(210.0)
        ->and((float) $newProduct->stock)->toBe(1.0);

    expect(InventoryMovement::query()->where('product_id', $newProduct->id)->where('type', 'in')->exists())->toBeTrue();
});

test('a trade-in on a credit sale reduces the clients pending debt via an auto credit payment', function () {
    $user = tradeInTestUser();
    $product = tradeInTestProduct(['sale_price' => 300]);
    $category = Category::firstOrCreate(['name' => 'Telefonos TradeIn Credito']);
    $client = Client::create([
        'name' => 'Cliente con equipo a cambio',
        'code' => 'CRED-TRADEIN',
        'credit_enabled' => true,
        'credit_limit' => 1000,
        'credit_days' => 30,
    ]);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'credit',
        'client_id' => $client->id,
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'discount' => 0]]),
        'trade_ins' => json_encode([[
            'imei' => 'CREDIT-IMEI-200',
            'name' => 'Samsung Galaxy A20',
            'category_id' => $category->id,
            'trade_in_value' => 100,
            'sale_price' => 150,
        ]]),
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->latest('id')->firstOrFail();

    $payment = CreditPayment::query()->where('sale_id', $sale->id)->firstOrFail();
    expect((float) $payment->amount)->toBe(100.0)
        ->and($payment->payment_type)->toBe('other')
        ->and($payment->caja_session_id)->toBeNull();

    expect(app(CreditService::class)->pendingDebt($client))->toBe(200.0);
});

test('a returning phone is recognized by imei and reactivates the same product instead of duplicating it', function () {
    $user = tradeInTestUser();
    $phone = Product::create([
        'category_id' => Category::firstOrCreate(['name' => 'Telefonos Retorno'])->id,
        'name' => 'iPhone X 64GB',
        'code' => 'PHN-RETURN-001',
        'condition' => 'used',
        'imei' => 'RETURN-IMEI-001',
        'purchase_price' => 80,
        'sale_price' => 180,
        'stock' => 1,
        'unit' => 'unidad',
        'status' => 'active',
    ]);

    $accessory = tradeInTestProduct(['name' => 'Funda protectora', 'sale_price' => 50]);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $phone->id, 'quantity' => 1, 'discount' => 0]]),
        'amount_received' => 180,
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect((float) $phone->fresh()->stock)->toBe(0.0);
    $phone->delete();
    expect($phone->fresh()->trashed())->toBeTrue();

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $accessory->id, 'quantity' => 1, 'discount' => 0]]),
        'trade_ins' => json_encode([[
            'imei' => 'RETURN-IMEI-001',
            'name' => 'iPhone X 64GB (retornado)',
            'category_id' => Category::firstOrCreate(['name' => 'Telefonos Retorno'])->id,
            'trade_in_value' => 30,
            'sale_price' => 120,
        ]]),
        'amount_received' => 20,
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $secondSale = Sale::query()->latest('id')->firstOrFail();
    $tradeIn = PhoneTradeIn::query()->where('sale_id', $secondSale->id)->firstOrFail();

    expect($tradeIn->product_id)->toBe($phone->id)
        ->and($tradeIn->was_returning_phone)->toBeTrue();

    $reactivated = Product::query()->withTrashed()->findOrFail($phone->id);
    expect($reactivated->trashed())->toBeFalse()
        ->and((float) $reactivated->stock)->toBe(1.0)
        ->and(Product::query()->where('imei', 'RETURN-IMEI-001')->count())->toBe(1);
});

test('the trade-in imei lookup endpoint reports an unknown imei and a known one with its last sale', function () {
    $user = tradeInTestUser();

    $this->actingAs($user)
        ->getJson(route('facturacion.pos-trade-in-imei', ['imei' => 'UNKNOWN-IMEI']))
        ->assertOk()
        ->assertJson(['exists' => false]);

    $phone = Product::create([
        'category_id' => Category::firstOrCreate(['name' => 'Telefonos Lookup'])->id,
        'name' => 'iPhone 8 64GB',
        'code' => 'PHN-LOOKUP-001',
        'condition' => 'used',
        'imei' => 'LOOKUP-IMEI-001',
        'purchase_price' => 60,
        'sale_price' => 130,
        'stock' => 1,
        'unit' => 'unidad',
        'status' => 'active',
    ]);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $phone->id, 'quantity' => 1, 'discount' => 0]]),
        'amount_received' => 130,
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->getJson(route('facturacion.pos-trade-in-imei', ['imei' => 'LOOKUP-IMEI-001']))
        ->assertOk()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('product.name', 'iPhone 8 64GB')
        ->assertJsonPath('product.trashed', false)
        ->assertJsonPath('product.lastSale.invoice_number', $sale->invoice_number);
});

test('a trade-in value larger than the sale total is rejected and rolls back the sale', function () {
    $user = tradeInTestUser();
    $product = tradeInTestProduct(['sale_price' => 10]);
    $category = Category::firstOrCreate(['name' => 'Telefonos TradeIn Exceso']);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'discount' => 0]]),
        'trade_ins' => json_encode([[
            'imei' => 'OVER-IMEI-300',
            'name' => 'Equipo caro',
            'category_id' => $category->id,
            'trade_in_value' => 50,
            'sale_price' => 60,
        ]]),
        'amount_received' => 10,
        'request_token' => (string) Str::uuid(),
    ])->assertSessionHasErrors('items');

    expect(Sale::query()->count())->toBe(0)
        ->and(Product::query()->where('imei', 'OVER-IMEI-300')->exists())->toBeFalse();
});

test('duplicate imeis in the same trade-in submission are rejected', function () {
    $user = tradeInTestUser();
    $product = tradeInTestProduct();
    $category = Category::firstOrCreate(['name' => 'Telefonos TradeIn Duplicado']);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'discount' => 0]]),
        'trade_ins' => json_encode([
            [
                'imei' => 'DUP-IMEI-400',
                'name' => 'Equipo uno',
                'category_id' => $category->id,
                'trade_in_value' => 10,
                'sale_price' => 20,
            ],
            [
                'imei' => 'DUP-IMEI-400',
                'name' => 'Equipo dos',
                'category_id' => $category->id,
                'trade_in_value' => 10,
                'sale_price' => 20,
            ],
        ]),
        'amount_received' => 300,
        'request_token' => (string) Str::uuid(),
    ])->assertSessionHasErrors('trade_ins');

    expect(Sale::query()->count())->toBe(0);
});

test('the cash drawer does not count the trade-in value as cash received', function () {
    $user = tradeInTestUser();
    $product = tradeInTestProduct(['sale_price' => 500]);
    $category = Category::firstOrCreate(['name' => 'Telefonos TradeIn Arqueo']);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'discount' => 0]]),
        'trade_ins' => json_encode([[
            'imei' => 'ARQ-IMEI-500',
            'name' => 'iPhone recibido',
            'category_id' => $category->id,
            'trade_in_value' => 200,
            'sale_price' => 260,
        ]]),
        'amount_received' => 300,
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale = Sale::query()->latest('id')->firstOrFail();
    expect((float) $sale->total)->toBe(500.0)->and((float) $sale->trade_in_value)->toBe(200.0);

    // En caja solo entraron $300; el equipo no es efectivo.
    $summary = $this->actingAs($user)->get(route('arqueo.index'))
        ->assertOk()
        ->viewData('closingSummary');

    $opening = (float) $summary['opening_amount'];
    expect((float) $summary['cash_sales_total'])->toEqual(300.0)
        ->and((float) $summary['expected_cash_total'])->toEqual($opening + 300.0);
});

test('an imei that is still in stock cannot be taken as a trade-in', function () {
    $user = tradeInTestUser();
    $product = tradeInTestProduct(['sale_price' => 400]);
    $category = Category::firstOrCreate(['name' => 'Telefonos TradeIn EnStock']);

    $inStock = tradeInTestProduct([
        'name' => 'Galaxy S21 en bodega',
        'imei' => 'STOCK-IMEI-900',
        'stock' => 1,
        'sale_price' => 350,
        'purchase_price' => 250,
    ]);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'discount' => 0]]),
        'trade_ins' => json_encode([[
            'imei' => 'STOCK-IMEI-900',
            'name' => 'Galaxy S21 recibido',
            'category_id' => $category->id,
            'trade_in_value' => 120,
            'sale_price' => 300,
        ]]),
        'amount_received' => 400,
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect();

    $inStock->refresh();
    expect((float) $inStock->stock)->toBe(1.0)
        ->and((float) $inStock->purchase_price)->toBe(250.0)
        ->and($inStock->name)->toBe('Galaxy S21 en bodega')
        ->and(PhoneTradeIn::query()->count())->toBe(0)
        ->and(Sale::query()->count())->toBe(0);
});

test('the pos escapes product and trade-in text before injecting it into the page', function () {
    $user = tradeInTestUser();

    $html = $this->actingAs($user)->get(route('facturacion.pos'))->assertOk()->getContent();

    foreach ([
        'escapeHeldText(p.name)',
        'escapeHeldText(p.lastSale.invoice_number)',
        'escapeHeldText(t.name)',
        'escapeHeldText(t.imei)',
    ] as $escaped) {
        expect($html)->toContain($escaped);
    }

    expect($html)->not->toContain('<strong>${p.name}</strong>')
        ->and($html)->not->toContain('${t.imei}$');
});
