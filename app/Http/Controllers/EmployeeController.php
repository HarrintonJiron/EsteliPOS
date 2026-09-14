<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('planilla.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('planilla.employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20|unique:employees',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'contract_type' => 'required|in:full_time,part_time,temporary,seasonal',
            'payment_frequency' => 'required|in:weekly,biweekly,monthly',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
        ]);

        Employee::create([
            ...$validated,
            'phone' => $validated['phone'] ?? '',
            'address' => $validated['address'] ?? '',
        ]);

        return redirect()->route('planilla.index')
            ->with('success', 'Empleado registrado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $vacationBalance = $this->payrollService->calculateVacationBalance($employee);
        $benefits = $this->payrollService->calculateBenefits($employee);
        $leaveRequests = $employee->leaveRequests()->latest()->get();

        return view('planilla.employees.show', compact('employee', 'vacationBalance', 'benefits', 'leaveRequests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        return view('planilla.employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20|unique:employees,cedula,'.$employee->id,
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'contract_type' => 'required|in:full_time,part_time,temporary,seasonal',
            'payment_frequency' => 'required|in:weekly,biweekly,monthly',
            'is_active' => 'boolean',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
        ]);

        $employee->update($validated);

        return redirect()->route('planilla.index')
            ->with('success', 'Empleado actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('planilla.index')
            ->with('success', 'Empleado eliminado correctamente');
    }
}
