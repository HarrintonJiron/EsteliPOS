<?php

namespace App\Http\Controllers;

use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NominaController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(): View
    {
        $period = $this->payrollService->resolvePeriodDates(request('month'));
        $startDate = $period['start'];
        $endDate = $period['end'];
        $payrollReport = $this->payrollService->generatePayrollReport($startDate, $endDate);
        $trend = $this->payrollService->getPayrollTrend(6, $startDate);
        $isPaid = $this->payrollService->isPeriodPaid($startDate);
        $paymentSummary = $this->payrollService->getPeriodPaymentSummary($startDate);

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
            'selectedMonth' => $period['selected_month'],
            'trend' => $trend,
            'charts' => $charts,
            'isPaid' => $isPaid,
            'paidAt' => $paymentSummary['paid_at'],
            'paidByName' => $paymentSummary['paid_by_name'],
        ]);
    }

    public function pay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $period = $this->payrollService->resolvePeriodDates($validated['month']);

        try {
            $result = $this->payrollService->payPayroll(
                $period['start'],
                $period['end'],
                (int) auth()->id()
            );
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('nomina.index', ['month' => $validated['month']])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('nomina.index', ['month' => $validated['month']])
            ->with('success', sprintf(
                'Nómina pagada correctamente. %d empleados · Neto C$ %s',
                $result['employees_count'],
                number_format($result['net_salary'], 2)
            ));
    }

    public function ticket(Request $request): View
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $period = $this->payrollService->resolvePeriodDates($validated['month']);
        $ticketData = $this->payrollService->getPayrollTicketData($period['start'], $period['end']);

        return view('planilla.nomina-ticket', [
            'ticketData' => $ticketData,
            'startDate' => $period['start'],
            'endDate' => $period['end'],
            'selectedMonth' => $period['selected_month'],
        ]);
    }
}
