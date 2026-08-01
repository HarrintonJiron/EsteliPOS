<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function productionSalesUser(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

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

beforeEach(function () {
    Tax::query()->update(['is_default' => false]);
    Tax::firstOrCreate(
        ['code' => 'EXENTO-INTEGRIDAD'],
        ['name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true],
    )->update(['is_default' => true, 'is_active' => true]);
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
