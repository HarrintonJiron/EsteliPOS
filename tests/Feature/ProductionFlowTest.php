<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\ExchangeRate;
use App\Models\LeaveRequest;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\RepairOrder;
use App\Models\Sale;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
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

test('nomina can be paid and ticket printed', function () {
    $admin = flowAdmin();
    $month = now()->format('Y-m');

    Employee::create([
        'name' => 'Empleado Nomina',
        'position' => 'Operativo',
        'salary' => 12000,
        'phone' => null,
        'address' => null,
        'contract_type' => 'full_time',
        'payment_frequency' => 'monthly',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->get(route('nomina.index', ['month' => $month]))
        ->assertOk()
        ->assertSee('Pagar nómina')
        ->assertSee('Imprimir ticket');

    $this->actingAs($admin)->post(route('nomina.pay'), ['month' => $month])
        ->assertRedirect(route('nomina.index', ['month' => $month]))
        ->assertSessionHas('success');

    expect(Payroll::query()->where('status', 'paid')->count())->toBe(1);

    $this->actingAs($admin)->get(route('nomina.ticket', ['month' => $month]))
        ->assertOk()
        ->assertSee('NÓMINA PAGADA')
        ->assertSee('Empleado Nomina')
        ->assertSee('size: 80mm auto');

    $this->actingAs($admin)->get(route('nomina.index', ['month' => $month]))
        ->assertOk()
        ->assertSee('Pagada')
        ->assertDontSee('Pagar nómina');
});

test('loan can be created with string months from form', function () {
    $admin = flowAdmin();
    $employee = Employee::create([
        'name' => 'Empleado Nuevo Prestamo',
        'position' => 'Operativo',
        'salary' => 10000,
        'phone' => null,
        'address' => null,
        'contract_type' => 'full_time',
        'payment_frequency' => 'monthly',
    ]);

    $this->actingAs($admin)->post(route('loans.store'), [
        'employee_id' => (string) $employee->id,
        'type' => 'loan',
        'amount' => '1200',
        'months' => '12',
        'start_date' => now()->toDateString(),
        'reason' => 'Prueba',
    ])->assertRedirect(route('loans.index'));

    $loan = Loan::query()->latest('id')->first();

    expect($loan)->not->toBeNull()
        ->and($loan->months)->toBe(12)
        ->and((float) $loan->monthly_payment)->toBe(100.0)
        ->and($loan->end_date->equalTo(
            Carbon::parse($loan->start_date)->addMonths(12)
        ))->toBeTrue();
});

test('loan show loads when employee was soft deleted', function () {
    $admin = flowAdmin();
    $employee = Employee::create([
        'name' => 'Empleado Prestamo',
        'position' => 'Operativo',
        'salary' => 10000,
        'phone' => null,
        'address' => null,
        'contract_type' => 'full_time',
        'payment_frequency' => 'monthly',
    ]);

    $loan = Loan::create([
        'employee_id' => $employee->id,
        'type' => 'loan',
        'amount' => 1000,
        'monthly_payment' => 100,
        'months' => 10,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(10)->toDateString(),
        'remaining_balance' => 1000,
        'status' => 'pending',
    ]);

    $employee->delete();

    $this->actingAs($admin)->get(route('loans.show', $loan))
        ->assertOk()
        ->assertSee('Empleado Prestamo');
});

test('leave show loads when employee was soft deleted', function () {
    $admin = flowAdmin();
    $employee = Employee::create([
        'name' => 'Empleado Permiso',
        'position' => 'Operativo',
        'salary' => 10000,
        'phone' => null,
        'address' => null,
        'contract_type' => 'full_time',
        'payment_frequency' => 'monthly',
    ]);

    $leave = LeaveRequest::create([
        'employee_id' => $employee->id,
        'type' => 'vacation',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'days' => 3,
        'status' => 'pending',
    ]);

    $employee->delete();

    $this->actingAs($admin)->get(route('leave.show', $leave))
        ->assertOk()
        ->assertSee('Empleado Permiso');
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
