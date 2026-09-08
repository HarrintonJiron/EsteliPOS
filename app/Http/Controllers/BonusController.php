<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\Employee;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bonuses = Bonus::with('employee', 'approvedBy')->latest()->get();
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.bonuses.index', compact('bonuses', 'employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.bonuses.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:performance,sales,attendance,christmas,productivity,other',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $validated['status'] = 'pending';

        Bonus::create($validated);

        return redirect()->route('bonuses.index')
            ->with('success', 'Bono creado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bonus $bonus)
    {
        $bonus->load('employee', 'approvedBy');

        return view('planilla.bonuses.show', compact('bonus'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bonus $bonus)
    {
        $bonus->load('employee');

        return view('planilla.bonuses.edit', compact('bonus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bonus $bonus)
    {
        $validated = $request->validate([
            'type' => 'required|in:performance,sales,attendance,christmas,productivity,other',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $bonus->update($validated);

        return redirect()->route('bonuses.index')
            ->with('success', 'Bono actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bonus $bonus)
    {
        $bonus->delete();

        return redirect()->route('bonuses.index')
            ->with('success', 'Bono eliminado correctamente');
    }

    /**
     * Aprobar bono
     */
    public function approve(Request $request, Bonus $bonus)
    {
        $bonus->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('bonuses.index')
            ->with('success', 'Bono aprobado correctamente');
    }

    /**
     * Marcar bono como pagado
     */
    public function markAsPaid(Request $request, Bonus $bonus)
    {
        $bonus->update([
            'status' => 'paid',
        ]);

        return redirect()->route('bonuses.index')
            ->with('success', 'Bono marcado como pagado');
    }
}
