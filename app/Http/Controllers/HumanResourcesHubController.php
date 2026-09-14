<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\PerformanceEvaluation;
use App\Services\ExecutiveAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HumanResourcesHubController extends Controller
{
    public function __construct(private ExecutiveAnalyticsService $analytics) {}

    public function hub(): View
    {
        return view('planilla.rrhh.hub', $this->analytics->humanResources());
    }

    public function directory(): View
    {
        return view('planilla.rrhh.directory', $this->analytics->humanResources());
    }

    public function organigram(): View
    {
        return view('planilla.rrhh.organigram', $this->analytics->humanResources());
    }

    public function attendance(): View
    {
        return view('planilla.rrhh.attendance', $this->analytics->humanResources());
    }

    public function storeAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
            'status' => ['required', 'in:present,late,absent,leave'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        AttendanceRecord::query()->updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'work_date' => $validated['work_date']],
            [...$validated, 'recorded_by' => $request->user()?->id]
        );

        return back()->with('success', 'Asistencia registrada correctamente.');
    }

    public function shifts(): View
    {
        return view('planilla.rrhh.shifts', $this->analytics->humanResources());
    }

    public function thirteenth(): View
    {
        return view('planilla.rrhh.thirteenth', $this->analytics->humanResources());
    }

    public function inss(): View
    {
        return view('planilla.rrhh.inss', $this->analytics->humanResources());
    }

    public function evaluations(): View
    {
        return view('planilla.rrhh.evaluations', $this->analytics->humanResources());
    }

    public function storeEvaluation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'evaluation_date' => ['required', 'date'],
            'period' => ['required', 'string', 'max:30'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'strengths' => ['nullable', 'string', 'max:1000'],
            'improvements' => ['nullable', 'string', 'max:1000'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        PerformanceEvaluation::query()->create([...$validated, 'evaluator_id' => $request->user()?->id]);

        return back()->with('success', 'Evaluación registrada correctamente.');
    }
}
