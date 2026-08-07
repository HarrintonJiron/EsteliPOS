<?php

namespace App\Http\Controllers;

use App\Services\PayrollService;
use Carbon\Carbon;

class NominaController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index()
    {
        $startDate = Carbon::now()->startOfMonth();

        if (request()->filled('month')) {
            $startDate = Carbon::createFromFormat('Y-m', request('month'))->startOfMonth();
        } elseif (request()->has('month') && request()->has('year')) {
            $startDate = Carbon::create((int) request('year'), (int) request('month'), 1)->startOfMonth();
        }

        $endDate = $startDate->copy()->endOfMonth();
        $payrollReport = $this->payrollService->generatePayrollReport($startDate, $endDate);
        $trend = $this->payrollService->getPayrollTrend(6, $startDate);

        $charts = [
            'salary_by_employee' => collect($payrollReport['employees'])
                ->sortByDesc('net_salary')
                ->values()
                ->map(fn (array $row) => [
                    'name' => $row['employee_name'],
                    'net' => round($row['net_salary'], 2),
                    'gross' => round($row['gross_salary'], 2),
                    'deductions' => round($row['total_deductions'], 2),
                ])
                ->all(),
            'deduction_breakdown' => [
                ['label' => 'INSS', 'value' => $payrollReport['totals']['inss_deduction']],
                ['label' => 'IR', 'value' => $payrollReport['totals']['ir_deduction']],
                ['label' => 'Otras', 'value' => $payrollReport['totals']['other_deductions']],
                ['label' => 'Préstamos', 'value' => $payrollReport['totals']['loan_payments']],
            ],
            'composition' => [
                ['label' => 'Salario base', 'value' => $payrollReport['totals']['base_salary']],
                ['label' => 'Bonos', 'value' => $payrollReport['totals']['bonuses']],
                ['label' => 'Deducciones', 'value' => $payrollReport['totals']['total_deductions']],
            ],
        ];

        return view('planilla.nomina', [
            'payrollReport' => $payrollReport,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedMonth' => $startDate->format('Y-m'),
            'trend' => $trend,
            'charts' => $charts,
        ]);
    }
}
