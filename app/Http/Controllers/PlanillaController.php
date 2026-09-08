<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanillaController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index()
    {
        $referenceDate = Carbon::now();

        if (request()->filled('month')) {
            $referenceDate = Carbon::createFromFormat('Y-m', request('month'))->startOfMonth();
        }

        $dashboard = $this->payrollService->getDashboardData($referenceDate);
        $employees = Employee::orderByDesc('is_active')->orderBy('name')->get();
        $analytics = $this->payrollService->getPlanillaDashboardAnalyticsPayload($dashboard['month']);

        return view('planilla.index', [
            'employees' => $employees,
            'dashboard' => $dashboard,
            'selectedMonth' => $dashboard['month'],
            'chartsUrl' => route('planilla.charts'),
            'initialChartData' => $analytics,
        ]);
    }

    public function charts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        return response()->json(
            $this->payrollService->getPlanillaDashboardAnalyticsPayload($validated['month'])
        );
    }
}
