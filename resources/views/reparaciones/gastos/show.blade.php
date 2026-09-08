@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Detalle de Gasto Operativo')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Detalle de Gasto Operativo</h1>
            <p class="page-subtitle">{{ $expense->description }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reparaciones.gastos.index') }}" class="btn-outline">← Listado</a>
            @if($expense->status !== \App\Models\OperationalExpense::STATUS_CANCELLED && (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reparaciones.edit_expenses')))
                <a href="{{ route('reparaciones.gastos.edit', $expense) }}" class="btn-primary">Editar</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="card p-4 bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card p-4">
            <p class="text-xs text-slate-500">Monto</p>
            <p class="text-2xl font-bold text-rose-600">C$ {{ number_format($expense->amount, 2) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Fecha</p>
            <p class="text-lg font-bold text-slate-700">{{ $expense->expense_date->format('d/m/Y') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Método</p>
            <p class="text-lg font-bold text-slate-700">{{ $expense->payment_method_label }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Estado</p>
            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $expense->status_color }}">{{ $expense->status_label }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card p-5 space-y-4">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wide">Descripción</p>
                <p class="text-slate-800 font-semibold">{{ $expense->description }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wide">Observaciones</p>
                <p class="text-slate-700">{{ $expense->notes ?: 'Sin observaciones.' }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide">Cuenta contable</p>
                    <p class="text-slate-700">{{ $expense->account?->code ?? '—' }} {{ $expense->account?->name ?? '' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide">Responsable</p>
                    <p class="text-slate-700">{{ $expense->user?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide">Caja / Sesión</p>
                    <p class="text-slate-700">{{ $expense->cajaSession ? 'Caja #' . $expense->cajaSession->id . ' · ' . $expense->cajaSession->date->format('d/m/Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide">Orden relacionada</p>
                    <p class="text-slate-700">
                        @if($expense->repairOrder)
                            <a href="{{ route('reparaciones.show', $expense->repairOrder) }}" class="text-indigo-600 hover:underline">{{ $expense->repairOrder->order_number }}</a>
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="card p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">Integración Contable</h2>
            @if($journalEntry)
                <div class="rounded-xl bg-slate-50 p-4 text-sm space-y-2">
                    <p><strong>Asiento:</strong> <a href="{{ route('contabilidad.asientos.show', $journalEntry) }}" class="text-indigo-600 hover:underline">{{ $journalEntry->number }}</a></p>
                    <p><strong>Estado:</strong> {{ $journalEntry->status_label }}</p>
                    <p><strong>Referencia:</strong> {{ $journalEntry->reference ?: '—' }}</p>
                </div>
            @else
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    Este gasto no tiene un asiento posteado. Verifica si está en borrador o anulado.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection