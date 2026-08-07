<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\PayrollService;
use Carbon\Carbon;

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

        return view('planilla.index', [
            'employees' => $employees,
            'dashboard' => $dashboard,
            'selectedMonth' => $dashboard['month'],
        ]);
    }
}
