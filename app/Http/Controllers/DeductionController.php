<?php

namespace App\Http\Controllers;

use App\Models\Deduction;
use App\Models\Employee;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deductions = Deduction::with('employee', 'approvedBy')->latest()->get();
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.deductions.index', compact('deductions', 'employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.deductions.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:uniform,tools,damages,absence,late,other',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $validated['status'] = 'pending';

        Deduction::create($validated);

        return redirect()->route('deductions.index')
            ->with('success', 'Deducción creada correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Deduction $deduction)
    {
        $deduction->load('employee', 'approvedBy');

        return view('planilla.deductions.show', compact('deduction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deduction $deduction)
    {
        $deduction->load('employee');

        return view('planilla.deductions.edit', compact('deduction'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deduction $deduction)
    {
        $validated = $request->validate([
            'type' => 'required|in:uniform,tools,damages,absence,late,other',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $deduction->update($validated);

        return redirect()->route('deductions.index')
            ->with('success', 'Deducción actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deduction $deduction)
    {
        $deduction->delete();

        return redirect()->route('deductions.index')
            ->with('success', 'Deducción eliminada correctamente');
    }

    /**
     * Aprobar deducción
     */
    public function approve(Request $request, Deduction $deduction)
    {
        $deduction->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('deductions.index')
            ->with('success', 'Deducción aprobada correctamente');
    }

    /**
     * Marcar deducción como deducida
     */
    public function markAsDeducted(Request $request, Deduction $deduction)
    {
        $deduction->update([
            'status' => 'deducted',
        ]);

        return redirect()->route('deductions.index')
            ->with('success', 'Deducción marcada como deducida');
    }
}
