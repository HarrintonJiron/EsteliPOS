<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\RepairOrder;
use App\Models\Sale;
use App\Models\User;
use App\Services\PayrollService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    disableDemoSeederData();
});

function flowAdmin(): User
{
    enableDemoSeederData();
    test()->seed(DatabaseSeeder::class);

    return User::query()->where('email', 'admin@agroservicio.com')->firstOrFail();
}

test('admin can create an employee and view payroll report', function () {
    $admin = flowAdmin();

    $this->actingAs($admin)->post(route('employees.store'), [
        'name' => 'Empleado QA Producción',
        'cedula' => '001-280887-0001X',
        'position' => 'Cajero',
        'salary' => 15000,
        'contract_type' => 'full_time',
        'payment_frequency' => 'monthly',
        'phone' => '88881111',
    ])->assertRedirect(route('planilla.index'));

    $employee = Employee::query()->where('cedula', '001-280887-0001X')->firstOrFail();

    $report = app(PayrollService::class)->generatePayrollReport(
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($report['employees'])->not->toBeEmpty()
        ->and(collect($report['employees'])->pluck('employee_id'))->toContain($employee->id);

    $this->actingAs($admin)->get(route('nomina.index'))->assertOk();
});

test('admin can create and list exchange rates', function () {
    $admin = flowAdmin();

    $this->actingAs($admin)->post(route('settings.exchange-rates.store'), [
        'from_currency' => 'USD',
        'to_currency' => 'NIO',
        'rate' => 36.50,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ])->assertRedirect(route('settings.exchange-rates.index'));

    expect(ExchangeRate::query()->where('from_currency', 'USD')->exists())->toBeTrue();

    $this->actingAs($admin)->get(route('settings.exchange-rates.index'))->assertOk();
});

test('admin can create a proforma and convert it to sale', function () {
    $admin = flowAdmin();
    $product = Product::query()->where('stock', '>', 0)->firstOrFail();

    $this->actingAs($admin)->post(route('proformas.store'), [
        'items' => json_encode([[
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => (float) $product->sale_price,
            'discount' => 0,
        ]]),
    ])->assertRedirect();

    $proforma = Proforma::query()->latest('id')->firstOrFail();

    $this->actingAs($admin)->get(route('proformas.show', $proforma->id))->assertOk();

    $this->actingAs($admin)->post(route('proformas.convert', $proforma->id))
        ->assertRedirect();

    expect(Sale::query()->count())->toBeGreaterThan(0);
});

test('admin can register repair order and print ticket', function () {
    $admin = flowAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        'client_name' => 'Cliente Reparación QA',
        'client_phone' => '86665555',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 12',
        'problem_description' => 'No enciende — prueba producción',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'payment_type' => 'cash',
        'warranty_enabled' => true,
    ])->assertRedirect();

    $order = RepairOrder::query()->latest('id')->firstOrFail();

    $this->actingAs($admin)->get(route('reparaciones.show', $order->id))->assertOk();
    $this->actingAs($admin)->get(route('reparaciones.ticket', $order->id))->assertOk();
    $this->actingAs($admin)->get(route('reparaciones.pdf', $order->id))->assertOk();
});

test('credit client statement is accessible', function () {
    $admin = flowAdmin();
    $client = Client::query()->where('credit_enabled', true)->first()
        ?? Client::query()->firstOrFail();

    $this->actingAs($admin)->get(route('creditos.statement', $client->id))->assertOk();
    $this->actingAs($admin)->get(route('creditos.index'))->assertOk();
});

test('contabilidad dashboard and core reports load', function () {
    $admin = flowAdmin();

    $this->actingAs($admin)->get(route('contabilidad.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('contabilidad.estado-resultados.index'))->assertOk();
    $this->actingAs($admin)->get(route('contabilidad.cuentas.index'))->assertOk();
});

test('planilla submodules create forms load', function (string $routeName) {
    $this->actingAs(flowAdmin())->get(route($routeName))->assertOk();
})->with([
    'leave create' => ['leave.create'],
    'loan create' => ['loans.create'],
    'bonus create' => ['bonuses.create'],
    'deduction create' => ['deductions.create'],
    'employee create' => ['employees.create'],
]);
