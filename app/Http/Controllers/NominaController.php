<?php

namespace App\Http\Controllers;

use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NominaController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(): View
    {
        $analytics = $this->payrollService->getNominaAnalyticsPayload(request('month'));
        $report = $analytics['payrollReport'];

        return view('planilla.nomina', [
            'payrollReport' => $report,
            'startDate' => Carbon::createFromFormat('Y-m', $analytics['selectedMonth'])->startOfMonth(),
            'endDate' => Carbon::createFromFormat('Y-m', $analytics['selectedMonth'])->endOfMonth(),
            'selectedMonth' => $analytics['selectedMonth'],
            'trend' => $analytics['trend'],
            'charts' => $analytics['charts'],
            'isPaid' => $analytics['isPaid'],
            'paidAt' => $report['paid_at'] ?? null,
            'paidByName' => $analytics['paidByName'],
            'chartsUrl' => route('nomina.charts'),
            'initialChartData' => $analytics,
        ]);
    }

    public function charts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        return response()->json(
            $this->payrollService->getNominaAnalyticsPayload($validated['month'])
        );
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
