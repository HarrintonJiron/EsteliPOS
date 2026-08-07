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
            <p class="page-subtitle">Resumen de nómina, personal y pendientes · {{ $dashboard['period_label'] }}</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="month" value="{{ $selectedMonth }}" class="input-field w-auto">
            <button type="submit" class="btn-primary">Actualizar</button>
        </form>
    </div>

    {{-- KPIs principales --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-indigo-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Empleados activos</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['active_employees'] }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $stats['inactive_employees'] }} inactivos · {{ $stats['total_employees'] }} total</p>
        </div>

        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Nómina neta del mes</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">C$ {{ number_format($totals['net_salary'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Bruto C$ {{ number_format($totals['gross_salary'], 2) }}</p>
        </div>

        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-rose-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Deducciones del mes</p>
            <p class="mt-2 text-3xl font-bold text-rose-700">C$ {{ number_format($totals['total_deductions'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">INSS + IR + otras + préstamos</p>
        </div>

        <div class="card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-amber-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Pendientes de aprobación</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">{{ $stats['pending_total'] }}</p>
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
<script>
const money = (v) => 'C$ ' + Number(v).toLocaleString('es-NI', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
const trend = @json($dashboard['trend']);
const deductionBreakdown = @json($charts['deduction_breakdown']);
const salaryByEmployee = @json($charts['salary_by_employee']);
const contractDistribution = @json($charts['contract_distribution']);

Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#64748b';

new Chart(document.getElementById('payrollTrendChart'), {
    type: 'line',
    data: {
        labels: trend.map(i => i.label),
        datasets: [
            {
                label: 'Bruto',
                data: trend.map(i => i.gross),
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                fill: true,
                tension: 0.35,
            },
            {
                label: 'Neto',
                data: trend.map(i => i.net),
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.08)',
                fill: true,
                tension: 0.35,
            },
            {
                label: 'Deducciones',
                data: trend.map(i => i.deductions),
                borderColor: '#e11d48',
                tension: 0.35,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } },
            x: { grid: { display: false } },
        },
    },
});

new Chart(document.getElementById('deductionsChart'), {
    type: 'doughnut',
    data: {
        labels: deductionBreakdown.map(i => i.label),
        datasets: [{
            data: deductionBreakdown.map(i => i.value),
            backgroundColor: ['#4f46e5', '#f59e0b', '#f43f5e', '#0ea5e9'],
            borderWidth: 0,
            hoverOffset: 6,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } },
            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${money(ctx.raw)}` } },
        },
        cutout: '62%',
    },
});

new Chart(document.getElementById('salaryByEmployeeChart'), {
    type: 'bar',
    data: {
        labels: salaryByEmployee.map(i => i.name),
        datasets: [{
            label: 'Neto',
            data: salaryByEmployee.map(i => i.net),
            backgroundColor: '#6366f1',
            borderRadius: 8,
            maxBarThickness: 36,
        }],
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => money(ctx.raw) } } },
        scales: {
            x: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } },
            y: { grid: { display: false } },
        },
    },
});

new Chart(document.getElementById('contractChart'), {
    type: 'doughnut',
    data: {
        labels: contractDistribution.map(i => i.label),
        datasets: [{
            data: contractDistribution.map(i => i.value),
            backgroundColor: ['#0f766e', '#2563eb', '#d97706', '#7c3aed', '#64748b'],
            borderWidth: 0,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } },
        cutout: '55%',
    },
});
</script>
@endpush
@endsection
