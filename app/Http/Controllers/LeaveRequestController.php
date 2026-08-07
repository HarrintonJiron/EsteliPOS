<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaveRequests = LeaveRequest::with('employee', 'approvedBy')->latest()->get();
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.leave.index', compact('leaveRequests', 'employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)->get();

        return view('planilla.leave.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:vacation,sick,personal,maternity,paternity,bereavement,unpaid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        // Calcular días automáticamente
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $validated['days'] = $startDate->diffInDays($endDate) + 1;
        $validated['status'] = 'pending';

        LeaveRequest::create($validated);

        return redirect()->route('leave.index')
            ->with('success', 'Solicitud de permiso creada correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load('employee', 'approvedBy');

        return view('planilla.leave.show', compact('leaveRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        return view('planilla.leave.edit', compact('leaveRequest'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'type' => 'required|in:vacation,sick,personal,maternity,paternity,bereavement,unpaid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        // Recalcular días si cambian las fechas
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $validated['days'] = $startDate->diffInDays($endDate) + 1;

        $leaveRequest->update($validated);

        return redirect()->route('leave.index')
            ->with('success', 'Solicitud actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();

        return redirect()->route('leave.index')
            ->with('success', 'Solicitud eliminada correctamente');
    }

    /**
     * Aprobar solicitud de permiso
     */
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string',
        ]);

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('leave.index')
            ->with('success', 'Permiso aprobado correctamente');
    }

    /**
     * Rechazar solicitud de permiso
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->route('leave.index')
            ->with('success', 'Permiso rechazado correctamente');
    }
}
