@extends('layouts.app')

@section('title', 'Nómina Mensual')

@section('content')
@php
    $totals = $payrollReport['totals'];
@endphp

<div class="space-y-6">
    @include('planilla._nav')

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Nómina · {{ $startDate->translatedFormat('F Y') }}</h1>
            <p class="page-subtitle">
                {{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }} ·
                {{ count($payrollReport['employees']) }} empleados
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="month" value="{{ $selectedMonth }}" class="input-field w-auto">
            <button type="submit" class="btn-primary">Ver período</button>
        </form>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="card p-4 border-t-4 border-slate-400">
            <p class="text-xs uppercase tracking-wide text-slate-500">Salario base</p>
            <p class="mt-2 text-lg font-bold text-slate-800">C$ {{ number_format($totals['base_salary'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-emerald-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Bonos</p>
            <p class="mt-2 text-lg font-bold text-emerald-700">C$ {{ number_format($totals['bonuses'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-indigo-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Bruto</p>
            <p class="mt-2 text-lg font-bold text-indigo-700">C$ {{ number_format($totals['gross_salary'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-orange-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">INSS + IR</p>
            <p class="mt-2 text-lg font-bold text-orange-700">C$ {{ number_format($totals['inss_deduction'] + $totals['ir_deduction'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-rose-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total deducciones</p>
            <p class="mt-2 text-lg font-bold text-rose-700">C$ {{ number_format($totals['total_deductions'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-violet-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Neto a pagar</p>
            <p class="mt-2 text-lg font-bold text-violet-700">C$ {{ number_format($totals['net_salary'], 2) }}</p>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="card p-5 xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-800">Comparativo por empleado</h2>
                    <p class="text-xs text-slate-500">Bruto vs neto del período</p>
                </div>
                <div class="flex gap-4 text-xs">
                    <span class="text-indigo-600">● Bruto</span>
                    <span class="text-emerald-600">● Neto</span>
                </div>
            </div>
            <div class="h-80">
                <canvas id="employeePayrollChart"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Desglose de deducciones</h2>
                <p class="text-xs text-slate-500">Distribución del mes</p>
            </div>
            <div class="h-80">
                <canvas id="nominaDeductionsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="font-semibold text-slate-800">Tendencia reciente</h2>
                <p class="text-xs text-slate-500">Últimos 6 meses de nómina</p>
            </div>
            <div class="flex gap-4 text-xs">
                <span class="text-indigo-600">● Bruto</span>
                <span class="text-emerald-600">● Neto</span>
                <span class="text-rose-600">● Deducciones</span>
            </div>
        </div>
        <div class="h-64">
            <canvas id="nominaTrendChart"></canvas>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Detalle de nómina</h2>
            <p class="text-xs text-slate-500">Cálculo individual con bonos, deducciones y préstamos</p>
        </div>

        <div class="overflow-x-auto">
            <table class="table-agro w-full min-w-[1100px]">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Cargo</th>
                        <th>Base</th>
                        <th>Bonos</th>
                        <th>Bruto</th>
                        <th>INSS</th>
                        <th>IR</th>
                        <th>Deducciones</th>
                        <th>Préstamos</th>
                        <th>Neto</th>
                        <th>Días</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrollReport['employees'] as $employee)
                    <tr>
                        <td class="font-medium text-slate-800">{{ $employee['employee_name'] }}</td>
                        <td>{{ $employee['position'] }}</td>
                        <td>C$ {{ number_format($employee['base_salary'], 2) }}</td>
                        <td class="text-emerald-700">
                            @if($employee['bonuses'] > 0)
                                + C$ {{ number_format($employee['bonuses'], 2) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>C$ {{ number_format($employee['gross_salary'], 2) }}</td>
                        <td class="text-rose-600">- C$ {{ number_format($employee['inss_deduction'], 2) }}</td>
                        <td class="text-rose-600">- C$ {{ number_format($employee['ir_deduction'], 2) }}</td>
                        <td class="text-rose-600">
                            @if($employee['other_deductions'] > 0)
                                - C$ {{ number_format($employee['other_deductions'], 2) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-rose-600">
                            @if($employee['loan_payments'] > 0)
                                - C$ {{ number_format($employee['loan_payments'], 2) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="font-bold text-emerald-700">C$ {{ number_format($employee['net_salary'], 2) }}</td>
                        <td>
                            {{ $employee['worked_days'] }}
                            @if($employee['leave_days'] > 0)
                                <span class="text-xs text-slate-400">({{ $employee['leave_days'] }} permiso)</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="!py-10 text-center text-slate-500">
                            No hay empleados activos en la nómina de este período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($payrollReport['employees']) > 0)
                <tfoot>
                    <tr class="bg-slate-50 font-semibold">
                        <td class="px-6 py-3" colspan="2">Totales</td>
                        <td class="px-6 py-3">C$ {{ number_format($totals['base_salary'], 2) }}</td>
                        <td class="px-6 py-3 text-emerald-700">C$ {{ number_format($totals['bonuses'], 2) }}</td>
                        <td class="px-6 py-3">C$ {{ number_format($totals['gross_salary'], 2) }}</td>
                        <td class="px-6 py-3 text-rose-600">C$ {{ number_format($totals['inss_deduction'], 2) }}</td>
                        <td class="px-6 py-3 text-rose-600">C$ {{ number_format($totals['ir_deduction'], 2) }}</td>
                        <td class="px-6 py-3 text-rose-600">C$ {{ number_format($totals['other_deductions'], 2) }}</td>
                        <td class="px-6 py-3 text-rose-600">C$ {{ number_format($totals['loan_payments'], 2) }}</td>
                        <td class="px-6 py-3 text-emerald-700">C$ {{ number_format($totals['net_salary'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="card p-5 bg-gradient-to-br from-indigo-50 to-white border-indigo-100">
        <h3 class="font-semibold text-indigo-900 mb-2">Referencia legal — Nicaragua</h3>
        <ul class="text-sm text-indigo-800/80 space-y-1">
            <li>• INSS empleado: 6.25% del salario bruto (incluye bonos aprobados)</li>
            <li>• IR: tramos con exención anual de C$ 100,000</li>
            <li>• Bonos, deducciones y cuotas de préstamos activos se incluyen al estar aprobados</li>
            <li>• Vacaciones: 30 días/año · 13° mes: 1 mes de salario por año trabajado</li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const money = (v) => 'C$ ' + Number(v).toLocaleString('es-NI', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
const salaryByEmployee = @json($charts['salary_by_employee']);
const deductionBreakdown = @json($charts['deduction_breakdown']);
const trend = @json($trend);

Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#64748b';

new Chart(document.getElementById('employeePayrollChart'), {
    type: 'bar',
    data: {
        labels: salaryByEmployee.map(i => i.name),
        datasets: [
            {
                label: 'Bruto',
                data: salaryByEmployee.map(i => i.gross),
                backgroundColor: '#818cf8',
                borderRadius: 6,
                maxBarThickness: 28,
            },
            {
                label: 'Neto',
                data: salaryByEmployee.map(i => i.net),
                backgroundColor: '#34d399',
                borderRadius: 6,
                maxBarThickness: 28,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${money(ctx.raw)}` } },
        },
        scales: {
            y: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } },
            x: { grid: { display: false }, ticks: { maxRotation: 45, minRotation: 0 } },
        },
    },
});

new Chart(document.getElementById('nominaDeductionsChart'), {
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
        cutout: '60%',
    },
});

new Chart(document.getElementById('nominaTrendChart'), {
    type: 'line',
    data: {
        labels: trend.map(i => i.label),
        datasets: [
            { label: 'Bruto', data: trend.map(i => i.gross), borderColor: '#4f46e5', tension: 0.35, fill: false },
            { label: 'Neto', data: trend.map(i => i.net), borderColor: '#059669', tension: 0.35, fill: false },
            { label: 'Deducciones', data: trend.map(i => i.deductions), borderColor: '#e11d48', tension: 0.35, fill: false },
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
</script>
@endsection
