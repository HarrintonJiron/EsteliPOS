@extends('layouts.app')

@section('title', 'Gastos Operativos')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Gastos Operativos</h1>
            <p class="page-subtitle">Egresos de caja y banco vinculados al taller de reparaciones</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reparaciones.index') }}" class="btn-outline">← Reparaciones</a>
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reparaciones.create_expenses'))
                <a href="{{ route('reparaciones.gastos.create') }}" class="btn-primary">+ Nuevo Gasto</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="card p-4 bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4 border-l-4 border-slate-400">
            <p class="text-xs text-slate-500">Registros del mes</p>
            <p class="text-2xl font-bold text-slate-700">{{ $stats['count'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-rose-500">
            <p class="text-xs text-slate-500">Monto del mes</p>
            <p class="text-2xl font-bold text-rose-600">C$ {{ number_format($stats['amount'], 2) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Egresos en efectivo</p>
            <p class="text-2xl font-bold text-amber-600">C$ {{ number_format($stats['cash'], 2) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-red-500">
            <p class="text-xs text-slate-500">Anulados</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['cancelled'] }}</p>
        </div>
    </div>

    <div class="card p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Búsqueda</label>
                <input type="text" name="search" value="{{ request('search') }}" class="input-field" placeholder="Descripción u observaciones">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Estado</label>
                <select name="status" class="select-field">
                    <option value="">Todos</option>
                    @foreach([
                        \App\Models\OperationalExpense::STATUS_DRAFT => 'Borrador',
                        \App\Models\OperationalExpense::STATUS_REGISTERED => 'Registrado',
                        \App\Models\OperationalExpense::STATUS_CANCELLED => 'Anulado',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Método</label>
                <select name="payment_method" class="select-field">
                    <option value="">Todos</option>
                    @foreach(['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'other' => 'Otro'] as $value => $label)
                        <option value="{{ $value }}" {{ request('payment_method') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filtrar</button>
                <a href="{{ route('reparaciones.gastos.index') }}" class="btn-outline">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full table-agro">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Caja</th>
                    <th>Método</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th class="text-right">Monto</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                        <td>
                            <p class="font-semibold text-slate-800">{{ $expense->description }}</p>
                            <p class="text-xs text-slate-400">{{ $expense->account?->code ?? '6.1.99' }} · {{ $expense->account?->name ?? 'Gastos Operativos Taller' }}</p>
                        </td>
                        <td>
                            @if($expense->cajaSession)
                                <span class="text-sm">Caja #{{ $expense->cajaSession->id }}</span>
                                <p class="text-xs text-slate-400">{{ $expense->cajaSession->date->format('d/m/Y') }}</p>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $expense->payment_method_label }}</td>
                        <td>{{ $expense->user?->name ?? '—' }}</td>
                        <td>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $expense->status_color }}">{{ $expense->status_label }}</span>
                        </td>
                        <td class="text-right font-semibold">C$ {{ number_format($expense->amount, 2) }}</td>
                        <td>
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('reparaciones.gastos.show', $expense) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Ver">Ver</a>
                                @if($expense->status !== \App\Models\OperationalExpense::STATUS_CANCELLED && (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reparaciones.edit_expenses')))
                                    <a href="{{ route('reparaciones.gastos.edit', $expense) }}" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Editar">Editar</a>
                                @endif
                                @if($expense->status !== \App\Models\OperationalExpense::STATUS_CANCELLED && (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reparaciones.delete_expenses')))
                                    <form method="POST" action="{{ route('reparaciones.gastos.destroy', $expense) }}" onsubmit="return confirm('¿Anular este gasto operativo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Anular">Anular</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-12 text-slate-400">No hay gastos operativos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if($expenses->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>
@endsection