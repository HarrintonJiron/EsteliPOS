<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $loans = Loan::with('employee', 'approvedBy')->latest()->get();
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.loans.index', compact('loans', 'employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.loans.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:loan,advance,deduction',
            'amount' => 'required|numeric|min:0',
            'months' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $months = (int) $validated['months'];
        $amount = (float) $validated['amount'];

        $validated['months'] = $months;
        $validated['amount'] = $amount;
        $validated['monthly_payment'] = round($amount / $months, 2);
        $validated['end_date'] = Carbon::parse($validated['start_date'])->addMonths($months);
        $validated['remaining_balance'] = $amount;
        $validated['status'] = 'pending';

        Loan::create($validated);

        return redirect()->route('loans.index')
            ->with('success', 'Préstamo creado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Loan $loan)
    {
        $loan->load('employee', 'approvedBy');

        return view('planilla.loans.show', compact('loan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Loan $loan)
    {
        $loan->load('employee');

        return view('planilla.loans.edit', compact('loan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'type' => 'required|in:loan,advance,deduction',
            'amount' => 'required|numeric|min:0',
            'months' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $months = (int) $validated['months'];
        $amount = (float) $validated['amount'];

        $validated['months'] = $months;
        $validated['amount'] = $amount;
        $validated['monthly_payment'] = round($amount / $months, 2);
        $validated['end_date'] = Carbon::parse($validated['start_date'])->addMonths($months);
        $validated['remaining_balance'] = $amount;

        $loan->update($validated);

        return redirect()->route('loans.index')
            ->with('success', 'Préstamo actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Loan $loan)
    {
        $loan->delete();

        return redirect()->route('loans.index')
            ->with('success', 'Préstamo eliminado correctamente');
    }

    /**
     * Aprobar préstamo
     */
    public function approve(Request $request, Loan $loan)
    {
        $loan->update([
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('loans.index')
            ->with('success', 'Préstamo aprobado correctamente');
    }

    /**
     * Rechazar préstamo
     */
    public function reject(Request $request, Loan $loan)
    {
        $loan->update([
            'status' => 'cancelled',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('loans.index')
            ->with('success', 'Préstamo rechazado correctamente');
    }
}
