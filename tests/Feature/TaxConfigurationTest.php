<?php

use App\Models\Category;
use App\Models\Module;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function taxAdminUser(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

function taxableProduct(?Tax $tax = null): Product
{
    $category = Category::firstOrCreate(['name' => 'Pruebas']);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Producto fiscal',
        'code' => 'TAX-'.str()->random(8),
        'purchase_price' => 50,
        'sale_price' => 100,
        'stock' => 10,
        'unit' => 'unidad',
        'status' => 'active',
        'tax_id' => $tax?->id,
    ]);
}

test('tax configuration belongs to settings and does not require accounting to be active', function () {
    $admin = taxAdminUser();
    Module::where('slug', 'contabilidad')->firstOrFail()->update(['is_active' => false]);

    $this->actingAs($admin)->get(route('settings.taxes.index'))
        ->assertOk()
        ->assertSee('Configuración · Impuestos')
        ->assertDontSee('Libro Diario');
});

test('an inactive tax cannot remain the default tax', function () {
    $admin = taxAdminUser();
    $tax = Tax::create(['code' => 'IVA-15-X', 'name' => 'IVA', 'rate' => .15, 'is_default' => true, 'is_active' => true]);

    $this->actingAs($admin)->put(route('settings.taxes.update', $tax), [
        'code' => $tax->code,
        'name' => $tax->name,
        'rate' => 15,
        'is_default' => '1',
    ])->assertRedirect(route('settings.taxes.index'));

    expect($tax->fresh()->is_active)->toBeFalse()
        ->and($tax->fresh()->is_default)->toBeFalse()
        ->and(Tax::defaultRate())->toBe(0.0);
});

test('products ignore an inactive assigned tax and use the active default', function () {
    Tax::query()->update(['is_default' => false]);
    Tax::create(['code' => 'EXENTO-X', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    $inactiveTax = Tax::create(['code' => 'IVA-INACTIVO', 'name' => 'IVA inactivo', 'rate' => .15, 'is_default' => false, 'is_active' => false]);
    $product = taxableProduct($inactiveTax);

    expect($product->effectiveTaxRate())->toBe(0.0);
});

test('pos preview receives the effective product tax instead of a fixed fifteen percent', function () {
    $admin = taxAdminUser();
    Tax::query()->update(['is_default' => false]);
    Tax::create(['code' => 'EXENTO-POS', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    taxableProduct();

    $response = $this->actingAs($admin)->get(route('facturacion.pos'));

    $response->assertOk()
        ->assertSee('id="taxLabel"', false)
        ->assertSee('F1 Atajos')
        ->assertSee("e.key === 'F9'", false)
        ->assertSee('requestSubmit()', false)
        ->assertDontSee('const TAX_RATE = 0.15', false);
    expect((float) collect($response->viewData('products'))->first()['effective_tax_rate'])->toBe(0.0);
});

test('the legacy new sale url redirects to the point of sale', function () {
    $admin = taxAdminUser();

    $this->actingAs($admin)->get(route('facturacion.create'))
        ->assertRedirect(route('facturacion.pos'));
});

test('pos persists the same exempt total shown by its preview', function () {
    $admin = taxAdminUser();
    Tax::query()->update(['is_default' => false]);
    Tax::create(['code' => 'EXENTO-VENTA', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    $product = taxableProduct();

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($admin)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'discount' => 0,
        ]]),
        'amount_received' => 100,
        'order_discount_pct' => 0,
    ])->assertRedirect();

    $sale = Sale::latest('id')->firstOrFail();
    expect((float) $sale->subtotal)->toBe(100.0)
        ->and((float) $sale->tax_total)->toBe(0.0)
        ->and((float) $sale->total)->toBe(100.0);

    Storage::fake('public');
    Storage::disk('public')->put('company/main-logo.png', 'logo');
    Setting::set('company_logo', 'company/main-logo.png', 'string', 'general');
    Setting::set('ticket_logo', '', 'string', 'general');
    Setting::set('company_ruc', 'RUC-TEST-001', 'string', 'general');

    $this->actingAs($admin)->get(route('facturacion.receipt', ['saleId' => $sale->id, 'change' => 0]))
        ->assertOk()
        ->assertSee('data-paper-width="80mm"', false)
        ->assertSee('size: 80mm auto', false)
        ->assertSee('max-width: 68mm; max-height: 44mm', false)
        ->assertSee('company/main-logo.png', false)
        ->assertSee('RUC: RUC-TEST-001')
        ->assertSee('Imprimir ticket 80 mm');
});

test('pos accepts card payments and preserves their reference', function () {
    $admin = taxAdminUser();
    Tax::query()->update(['is_default' => false]);
    Tax::create(['code' => 'EXENTO-TARJETA', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    $product = taxableProduct();

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($admin)->post(route('facturacion.pos-store'), [
        'payment_type' => 'card',
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'discount' => 0,
        ]]),
        'reference_number' => 'TARJ-12345',
        'order_discount_pct' => 0,
    ])->assertRedirect();

    $sale = Sale::latest('id')->firstOrFail();
    expect($sale->payment_type)->toBe('transfer')
        ->and($sale->notes)->toContain('Pago con tarjeta')
        ->and($sale->notes)->toContain('Referencia: TARJ-12345');
});
