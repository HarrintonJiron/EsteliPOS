<?php

use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    disableDemoSeederData();
});

function chartAdmin(): User
{
    enableDemoSeederData();
    test()->seed(DatabaseSeeder::class);

    return User::query()->where('email', 'admin@agroservicio.com')->firstOrFail();
}

test('payroll chart endpoints return the series for the selected month', function () {
    $admin = chartAdmin();
    $month = now()->format('Y-m');

    $this->actingAs($admin)
        ->getJson(route('planilla.charts', ['month' => $month]))
        ->assertSuccessful()
        ->assertJsonPath('selectedMonth', $month)
        ->assertJsonStructure(['stats', 'totals', 'trend', 'charts']);

    $this->actingAs($admin)
        ->getJson(route('nomina.charts', ['month' => $month]))
        ->assertSuccessful()
        ->assertJsonPath('selectedMonth', $month)
        ->assertJsonStructure(['payrollReport', 'trend', 'charts']);
});

test('payroll pages load the dynamic chart integration', function () {
    $admin = chartAdmin();

    $this->actingAs($admin)
        ->get(route('planilla.index'))
        ->assertSuccessful()
        ->assertSee('initializeDynamicPayrollCharts', false)
        ->assertSee('const chartsUrl', false);

    $this->actingAs($admin)
        ->get(route('nomina.index'))
        ->assertSuccessful()
        ->assertSee('initializeDynamicPayrollCharts', false)
        ->assertSee('const chartsUrl', false);
});

test('dashboard chart totals exclude canceled sales', function () {
    chartAdmin();
    $baseline = app(DashboardService::class)->build(collect(['ventas']))['salesStats']['month'];
    $sale = Sale::query()->where('status', 'completed')->firstOrFail()->replicate();
    $sale->invoice_number = 'CANCELED-CHART-TEST';
    $sale->date = now();
    $sale->status = 'canceled';
    $sale->total = 999999;
    $sale->save();

    $afterCanceledSale = app(DashboardService::class)->build(collect(['ventas']))['salesStats']['month'];

    expect($afterCanceledSale)->toBe($baseline);
});
