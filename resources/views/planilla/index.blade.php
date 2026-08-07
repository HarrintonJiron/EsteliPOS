@extends('layouts.app')

@section('title', 'Dashboard de Planilla')

@section('content')
@php
    $stats = $dashboard['stats'];
    $totals = $dashboard['payroll']['totals'];
    $charts = $dashboard['charts'];
@endphp

<div class="space-y-6">
    @include('planilla._nav')

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Dashboard de Planilla</h1>
            <p id="dashboardPageSubtitle" class="page-subtitle">Resumen de nómina, personal y pendientes · {{ $dashboard['period_label'] }}</p>
        </div>

        <form method="GET" class="flex items-center gap-2" data-chart-filter>
            <input id="payrollMonthFilter" type="month" name="month" value="{{ $selectedMonth }}" class="input-field w-auto">
            <button type="submit" class="btn-primary">Actualizar</button>
        </form>
    </div>

    {{-- KPIs principales --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-indigo-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Empleados activos</p>
            <p class="mt-2 text-3xl font-bold text-slate-800" data-dashboard-stat="active-employees">{{ $stats['active_employees'] }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $stats['inactive_employees'] }} inactivos · {{ $stats['total_employees'] }} total</p>
        </div>

        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Nómina neta del mes</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700" data-dashboard-stat="net-salary">C$ {{ number_format($totals['net_salary'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Bruto C$ {{ number_format($totals['gross_salary'], 2) }}</p>
        </div>

        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-rose-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Deducciones del mes</p>
            <p class="mt-2 text-3xl font-bold text-rose-700" data-dashboard-stat="total-deductions">C$ {{ number_format($totals['total_deductions'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">INSS + IR + otras + préstamos</p>
        </div>

        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-amber-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Pendientes de aprobación</p>
            <p class="mt-2 text-3xl font-bold text-amber-700" data-dashboard-stat="pending-total">{{ $stats['pending_total'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Permisos, préstamos, bonos y deducciones</p>
        </div>
    </div>

    {{-- Accesos rápidos / alertas --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <a href="{{ route('leave.index') }}" class="card p-4 hover:border-emerald-400 transition group">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700 group-hover:text-emerald-700">Permisos</span>
                <span class="badge-{{ $stats['pending_leaves'] > 0 ? 'warning' : 'success' }}">{{ $stats['pending_leaves'] }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Solicitudes pendientes</p>
        </a>
        <a href="{{ route('loans.index') }}" class="card p-4 hover:border-amber-400 transition group">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700 group-hover:text-amber-700">Préstamos</span>
                <span class="badge-{{ $stats['pending_loans'] > 0 ? 'warning' : 'info' }}">{{ $stats['pending_loans'] }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['active_loans_count'] }} activos · saldo C$ {{ number_format($stats['active_loans_balance'], 2) }}</p>
        </a>
        <a href="{{ route('bonuses.index') }}" class="card p-4 hover:border-teal-400 transition group">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700 group-hover:text-teal-700">Bonos</span>
                <span class="badge-{{ $stats['pending_bonuses'] > 0 ? 'warning' : 'success' }}">{{ $stats['pending_bonuses'] }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Mes: C$ {{ number_format($totals['bonuses'], 2) }}</p>
        </a>
        <a href="{{ route('deductions.index') }}" class="card p-4 hover:border-rose-400 transition group">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700 group-hover:text-rose-700">Deducciones</span>
                <span class="badge-{{ $stats['pending_deductions'] > 0 ? 'warning' : 'info' }}">{{ $stats['pending_deductions'] }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Otras: C$ {{ number_format($totals['other_deductions'], 2) }}</p>
        </a>
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="card p-5 xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-800">Tendencia de nómina (6 meses)</h2>
                    <p class="text-xs text-slate-500">Bruto, neto y deducciones</p>
                </div>
                <div class="flex gap-4 text-xs">
                    <span class="text-indigo-600">● Bruto</span>
                    <span class="text-emerald-600">● Neto</span>
                    <span class="text-rose-600">● Deducciones</span>
                </div>
            </div>
            <div class="h-72">
                <canvas id="payrollTrendChart"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Composición de deducciones</h2>
                <p class="text-xs text-slate-500">{{ $dashboard['period_label'] }}</p>
            </div>
            <div class="h-72">
                <canvas id="deductionsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="card p-5 xl:col-span-2">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Salario neto por empleado</h2>
                <p class="text-xs text-slate-500">Top 8 del período seleccionado</p>
            </div>
            <div class="h-72">
                <canvas id="salaryByEmployeeChart"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Tipo de contrato</h2>
                <p class="text-xs text-slate-500">Personal activo</p>
            </div>
            <div class="h-72">
                <canvas id="contractChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Tabla empleados --}}
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
            <div>
                <h2 class="font-semibold text-slate-800">Personal</h2>
                <p class="text-xs text-slate-500">Gestión rápida de empleados</p>
            </div>
            <a href="{{ route('employees.create') }}" class="btn-primary text-sm">+ Nuevo empleado</a>
        </div>

        <div class="overflow-x-auto">
            <table class="table-agro w-full">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Cargo</th>
                        <th>Ingreso</th>
                        <th>Contrato</th>
                        <th>Salario</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td class="font-medium text-slate-800">{{ $employee->name }}</td>
                        <td>{{ $employee->cedula ?? '-' }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->hire_date ? $employee->hire_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $employee->contract_type_label }}</td>
                        <td class="font-medium">C$ {{ number_format($employee->salary, 2) }}</td>
                        <td>
                            @if($employee->is_active)
                                <span class="badge-success">Activo</span>
                            @else
                                <span class="badge-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="space-x-2 whitespace-nowrap">
                            <a href="{{ route('employees.show', $employee) }}" class="text-indigo-600 hover:underline">Ver</a>
                            <a href="{{ route('employees.edit', $employee) }}" class="text-slate-600 hover:underline">Editar</a>
                            <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('¿Desea eliminar este empleado?')" class="text-rose-600 hover:underline">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="!py-10 text-center text-slate-500">
                            No hay empleados registrados. Use “+ Nuevo empleado” para comenzar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>



@push('scripts')
@include('planilla.partials.dynamic-payroll-charts', [
    'chartsUrl' => $chartsUrl,
    'initialChartData' => $initialChartData,
    'mode' => 'dashboard',
    'monthInputId' => 'payrollMonthFilter',
])
@endpush
@endsection
