<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\RepairCreditPayment;
use App\Models\RepairOrder;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CreditService;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cellphoneOperationsAdmin(): User
{
    enableDemoSeederData();
    test()->seed(DatabaseSeeder::class);

    return User::query()->where('email', 'admin@agroservicio.com')->firstOrFail();
}

afterEach(fn () => disableDemoSeederData());

test('reservations and shipments share one operational module with guided forms', function () {
    $admin = cellphoneOperationsAdmin();

    $this->actingAs($admin)->get(route('operaciones-clientes.index'))
        ->assertOk()
        ->assertSee('Apartados y envíos')
        ->assertSee('Apartados recientes')
        ->assertSee('Envíos en proceso');

    $this->actingAs($admin)->get(route('apartados.create'))
        ->assertOk()
        ->assertSee('Busca por nombre, código, modelo, color o IMEI')
        ->assertSee('Cada toque suma una unidad')
        ->assertSee('Resumen de compra')
        ->assertSee('Saldo pendiente');

    $this->actingAs($admin)->get(route('envios.create'))
        ->assertOk()
        ->assertSee('Destino y contacto')
        ->assertSee('Estado del envío');
});

test('POS exposes reservation action and shipment can start from a completed sale', function () {
    $admin = cellphoneOperationsAdmin();
    $sale = Sale::query()->with('client')->firstOrFail();

    $this->actingAs($admin)->get(route('facturacion.pos'))
        ->assertOk()
        ->assertSee('Convertir ticket en apartado');

    $this->actingAs($admin)->get(route('envios.create', ['sale_id' => $sale->id]))
        ->assertOk()
        ->assertSee($sale->invoice_number)
        ->assertSee($sale->client->name);
});

test('reservation calculates authoritative prices and subtotals on the server', function () {
    $admin = cellphoneOperationsAdmin();
    $client = Client::query()->firstOrFail();
    $warehouse = Warehouse::default();
    $category = Category::query()->firstOrFail();
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $product = Product::create(['category_id' => $category->id, 'name' => 'Equipo precio seguro', 'code' => 'SECURE-PRICE', 'purchase_price' => 5000, 'sale_price' => 8000, 'stock' => 0, 'unit' => 'und', 'base_unit_id' => $unit->id, 'status' => 'active']);
    app(InventoryService::class)->stockIn($product, 3, 'test', 'Entrada de prueba', $admin->id, $warehouse->id);

    $this->actingAs($admin)->get(route('apartados.create', ['warehouse_id' => $warehouse->id]))
        ->assertOk()
        ->assertSee('Equipo precio seguro')
        ->assertSee('Existencia')
        ->assertSee('3 disp.');

    $response = $this->actingAs($admin)->post(route('apartados.store'), [
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'deposit' => 1000,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 2,
            'price_type' => 'retail',
            'unit_price' => 1,
        ]],
    ]);

    $reservation = Reservation::query()->latest('id')->firstOrFail();
    $response->assertRedirect(route('apartados.show', $reservation));
    expect((float) $reservation->total)->toBe(16000.0)
        ->and((float) $reservation->items()->firstOrFail()->subtotal)->toBe(16000.0);
});

test('an active reservation removes stock from POS availability without changing physical stock', function () {
    $admin = cellphoneOperationsAdmin();
    $client = Client::query()->firstOrFail();
    $warehouse = Warehouse::default();
    $category = Category::query()->firstOrFail();
    $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
    $product = Product::create(['category_id' => $category->id, 'name' => 'iPhone 15', 'code' => 'IPH-15-TEST', 'description' => 'Prueba', 'condition' => 'new', 'brand' => 'Apple', 'model' => 'iPhone 15', 'color' => 'Negro', 'imei' => '350000000000001', 'purchase_price' => 15000, 'sale_price' => 18000, 'stock' => 0, 'unit' => 'und', 'base_unit_id' => $unit->id, 'status' => 'active']);
    app(InventoryService::class)->stockIn($product, 5, 'test', 'Entrada de prueba', $admin->id, $warehouse->id);
    $reservation = Reservation::create(['number' => 'APT-TEST', 'client_id' => $client->id, 'user_id' => $admin->id, 'warehouse_id' => $warehouse->id, 'reserved_at' => now(), 'expires_at' => now()->addDay(), 'total' => 36000, 'deposit' => 5000, 'status' => 'active']);
    ReservationItem::create(['reservation_id' => $reservation->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 18000, 'subtotal' => 36000]);

    $serialized = app(PosCatalogService::class)->serializeProduct($product->fresh(), $warehouse->id);
    expect((float) $product->fresh()->stock)->toBe(5.0)
        ->and((float) $serialized['reserved_stock'])->toBe(2.0)
        ->and((float) $serialized['stock'])->toBe(3.0);

    expect(fn () => app(InventoryService::class)->stockOut($product, 4, 'test-sale', 'Venta de prueba', $admin->id, false, $warehouse->id))
        ->toThrow(RuntimeException::class, 'Stock disponible insuficiente');

    $this->actingAs($admin)->post(route('apartados.pay', $reservation), [
        'amount' => 6000,
        'payment_method' => 'cash',
    ])->assertRedirect();
    expect($reservation->fresh()->paid_amount)->toBe(11000.0)
        ->and($reservation->fresh()->balance)->toBe(25000.0);

    $this->actingAs($admin)->post(route('apartados.pay', $reservation), [
        'amount' => 26000,
        'payment_method' => 'cash',
    ])->assertSessionHas('error');
    expect($reservation->fresh()->paid_amount)->toBe(11000.0);

    $this->actingAs($admin)->patch(route('apartados.cancel', $reservation), [
        'reason' => 'Cliente desistió de la compra',
        'refund_amount' => 11000,
        'payment_method' => 'cash',
    ])->assertRedirect();
    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($reservation->fresh()->paid_amount)->toBe(0.0)
        ->and($product->fresh()->availableStock($warehouse->id))->toBe(5.0);
});

test('shipment stores a controlled department and is visible in its module', function () {
    $admin = cellphoneOperationsAdmin();
    $shipment = Shipment::create(['number' => 'ENV-TEST', 'client_id' => Client::query()->value('id'), 'user_id' => $admin->id, 'recipient_name' => 'María López', 'recipient_phone' => '88888888', 'department' => 'Matagalpa', 'municipality' => 'Sébaco', 'address' => 'Barrio Central', 'shipping_cost' => 120, 'status' => 'pending']);

    $this->actingAs($admin)->get(route('envios.show', $shipment))->assertOk()->assertSee('Matagalpa')->assertSee('María López');
});

test('repair credit contributes to client debt and payments reduce it', function () {
    $admin = cellphoneOperationsAdmin();
    $client = Client::create([
        'name' => 'Cliente crédito reparación',
        'phone' => '88880001',
        'email' => 'reparacion.credito@example.test',
        'credit_enabled' => true,
        'credit_limit' => 10000,
        'credit_days' => 30,
    ]);
    $order = RepairOrder::create(['order_number' => 'REP-CREDIT-TEST', 'client_id' => $client->id, 'client_name' => $client->name, 'device_brand' => 'Samsung', 'device_model' => 'A54', 'problem_description' => 'Pantalla', 'status' => 'ready', 'priority' => 'normal', 'user_id' => $admin->id, 'received_date' => today(), 'due_date' => today()->addDays(15), 'labor_cost' => 3000, 'parts_cost' => 2000, 'total' => 5000, 'advance_payment' => 1000, 'payment_type' => 'credit', 'payment_status' => 'partial']);

    expect(app(CreditService::class)->pendingDebt($client))->toBe(4000.0);
    RepairCreditPayment::create(['repair_order_id' => $order->id, 'client_id' => $client->id, 'user_id' => $admin->id, 'amount' => 1500, 'payment_date' => now(), 'payment_type' => 'cash']);
    expect(app(CreditService::class)->pendingDebt($client))->toBe(2500.0);
});
