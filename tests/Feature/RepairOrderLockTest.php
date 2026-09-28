<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\RepairOrder;
use App\Models\RepairOrderItem;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('repair order lock handling', function () {
    it('renders repair forms and exposes their brand and service endpoints', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $order = RepairOrder::create([
            'client_name' => 'Cliente de prueba',
            'device_brand' => 'Samsung',
            'device_model' => 'A54',
            'problem_description' => 'No enciende',
            'received_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->get(route('reparaciones.create'))->assertOk();
        $this->actingAs($user)->get(route('reparaciones.edit', $order))->assertOk();

        $this->actingAs($user)->postJson(route('device-brands.store'), ['name' => 'Marca nueva'])
            ->assertCreated()
            ->assertJsonPath('name', 'Marca nueva');
        $this->actingAs($user)->postJson(route('repair-services.store'), [
            'name' => 'Servicio nuevo',
            'price' => 125,
        ])->assertCreated()->assertJsonPath('name', 'Servicio nuevo');
    });

    it('stores a repair order with a pattern lock', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $response = $this->actingAs($user)->post('/reparaciones', [
            'client_name' => 'Ana García',
            'device_brand' => 'Samsung',
            'device_model' => 'Galaxy A54',
            'problem_description' => 'No enciende y muestra pantalla negra',
            'status' => 'received',
            'priority' => 'normal',
            'received_date' => now()->toDateString(),
            'received_time' => '08:35',
            'estimated_date' => now()->addDays(2)->toDateString(),
            'estimated_time' => '15:30',
            'include_warranty_policy' => '1',
            'warranty_days' => 45,
            'warranty_policy' => 'Garantía válida únicamente para el servicio realizado.',
            'payment_type' => 'cash',
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_orders', [
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
            'include_warranty_policy' => 1,
            'warranty_days' => 45,
            'warranty_policy' => 'Garantía válida únicamente para el servicio realizado.',
        ]);
        expect(substr((string) RepairOrder::latest('id')->value('received_time'), 0, 5))->toBe('08:35');
        expect(substr((string) RepairOrder::latest('id')->value('estimated_time'), 0, 5))->toBe('15:30');
        $orderId = RepairOrder::latest('id')->value('id');
        $this->actingAs($user)
            ->get(route('reparaciones.ticket', $orderId))
            ->assertOk()
            ->assertSee('Hora estimada:')
            ->assertSee('15:30')
            ->assertSee('GARANTÍA: 45 DÍAS')
            ->assertSee('Garantía válida únicamente para el servicio realizado.');
        $this->actingAs($user)->get(route('reparaciones.pdf', $orderId))
            ->assertOk()
            ->assertSee('Hora estimada:')
            ->assertSee('15:30');
        $this->actingAs($user)->get(route('reparaciones.index'))
            ->assertOk()
            ->assertSee('Entrega estimada')
            ->assertSee('Hora: 15:30')
            ->assertSee('A tiempo');
    });

    it('records the delivery date and time when the status changes to delivered', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $order = RepairOrder::create([
            'client_name' => 'Cliente de prueba',
            'device_brand' => 'Apple',
            'device_model' => 'iPhone 13',
            'problem_description' => 'Pantalla quebrada',
            'received_date' => now()->toDateString(),
            'received_time' => '09:10',
        ]);

        $this->travelTo(Carbon::parse('2026-08-02 16:45:00', 'America/Managua'));

        $this->actingAs($user)
            ->patch(route('reparaciones.status', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $deliveredOrder = $order->fresh();

        expect($deliveredOrder->status)->toBe('delivered')
            ->and($deliveredOrder->delivered_date->format('Y-m-d'))->toBe('2026-08-02')
            ->and(substr((string) $deliveredOrder->delivered_time, 0, 5))->toBe('16:45');
    });

    it('accepts browser time values with seconds while editing a repair order', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $order = RepairOrder::create([
            'client_name' => 'Cliente de prueba',
            'device_brand' => 'Xiaomi',
            'device_model' => 'Redmi Note 13',
            'problem_description' => 'No carga',
            'received_date' => '2026-08-01',
            'received_time' => '09:10:00',
        ]);

        $this->actingAs($user)->put(route('reparaciones.update', $order), [
            'client_name' => 'Cliente de prueba',
            'device_brand' => 'Xiaomi',
            'device_model' => 'Redmi Note 13',
            'problem_description' => 'No carga',
            'status' => 'delivered',
            'priority' => 'normal',
            'received_date' => '2026-08-01',
            'received_time' => '09:10:00',
            'estimated_date' => '2026-08-02',
            'estimated_time' => '15:30:00',
            'delivered_date' => '2026-08-02',
            'delivered_time' => '16:45:00',
            'include_warranty_policy' => '0',
            'payment_type' => 'cash',
        ])->assertRedirect(route('reparaciones.show', $order));

        $updatedOrder = $order->fresh();

        expect(substr((string) $updatedOrder->received_time, 0, 8))->toBe('09:10:00')
            ->and(substr((string) $updatedOrder->estimated_time, 0, 8))->toBe('15:30:00')
            ->and(substr((string) $updatedOrder->delivered_time, 0, 8))->toBe('16:45:00');
    });

    it('charges a ready repair once and creates its final invoice', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $client = Client::create(['name' => 'Carlos López', 'code' => 'CLI-REP-1', 'phone' => '8888-1111']);
        $category = Category::create(['name' => 'Repuestos de prueba']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Pantalla OLED',
            'code' => 'REP-OLED-1',
            'purchase_price' => 60,
            'sale_price' => 100,
            'stock' => 5,
            'unit' => 'unidad',
            'status' => 'active',
        ]);
        $order = RepairOrder::create([
            'order_number' => 'REP-900001',
            'client_id' => $client->id,
            'client_name' => $client->name,
            'client_phone' => $client->phone,
            'device_brand' => 'Samsung',
            'device_model' => 'A54',
            'problem_description' => 'Pantalla quebrada',
            'status' => 'ready',
            'received_date' => '2026-08-01',
            'received_time' => '09:00:00',
            'labor_cost' => 100,
            'parts_cost' => 200,
            'total' => 300,
            'discount_percentage' => 10,
            'advance_payment' => 50,
            'payment_status' => 'partial',
        ]);
        RepairOrderItem::create([
            'repair_order_id' => $order->id,
            'product_id' => $product->id,
            'description' => 'Pantalla OLED',
            'quantity' => 2,
            'price' => 100,
            'subtotal' => 200,
            'item_type' => 'part',
        ]);

        $this->actingAs($user)->post(route('reparaciones.bill', $order), [
            'payment_type' => 'cash',
            'amount_received' => 250,
        ])->assertRedirect(route('reparaciones.show', $order))
            ->assertSessionHas('change_amount', 30.0);

        $billedOrder = $order->fresh();
        $sale = Sale::with('details')->findOrFail($billedOrder->sale_id);

        expect($billedOrder->payment_status)->toBe('paid')
            ->and($billedOrder->balance())->toBe(0.0)
            ->and((float) $sale->total)->toBe(270.0)
            ->and((float) $sale->details->sum('subtotal'))->toBe(270.0)
            ->and($sale->details->pluck('description')->all())->toContain('Pantalla OLED')
            ->and($sale->details->pluck('description')->all())->toContain('Mano de obra - Samsung A54')
            ->and($product->fresh()->stock)->toBe(3)
            ->and(InventoryMovement::where('reference', 'repair_sale:'.$sale->id)->count())->toBe(1)
            ->and(JournalEntry::where('source_type', Sale::class)->where('source_id', $sale->id)->where('status', 'posted')->count())->toBe(1);

        $this->actingAs($user)->get(route('reparaciones.invoice-receipt', $order))
            ->assertOk()
            ->assertSee('Pantalla OLED')
            ->assertSee('PAGO FINAL');
        $this->actingAs($user)->get(route('reparaciones.invoice-pdf', $order))
            ->assertOk()
            ->assertSee('Mano de obra - Samsung A54')
            ->assertSee('Pago final:');

        $this->actingAs($user)->post(route('reparaciones.bill', $order), [
            'payment_type' => 'cash',
            'amount_received' => 250,
        ])->assertSessionHas('error', 'Esta orden ya fue facturada.');

        expect(Sale::count())->toBe(1)
            ->and($product->fresh()->stock)->toBe(3);
    });
});
