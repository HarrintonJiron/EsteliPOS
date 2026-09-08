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
            <h1 id="payrollPageTitle" class="page-title">Nómina · {{ $startDate->translatedFormat('F Y') }}</h1>
            <p id="payrollPageSubtitle" class="page-subtitle">
                {{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }} ·
                {{ count($payrollReport['employees']) }} empleados
                @if($isPaid)
                    · <span class="text-emerald-700 font-medium">Pagada</span>
                    @if($paidAt)
                        el {{ $paidAt->format('d/m/Y H:i') }}
                    @endif
                    @if($paidByName)
                        por {{ $paidByName }}
                    @endif
                @else
                    · <span class="text-amber-700 font-medium">Pendiente de pago</span>
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" class="flex items-center gap-2" data-chart-filter>
                <input id="payrollMonthFilter" type="month" name="month" value="{{ $selectedMonth }}" class="input-field w-auto">
                <button type="submit" class="btn-primary">Ver período</button>
            </form>

            @if(count($payrollReport['employees']) > 0)
                <a href="{{ route('nomina.ticket', ['month' => $selectedMonth]) }}"
                   target="_blank"
                   rel="noopener"
                   class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Imprimir ticket
                </a>

                @unless($isPaid)
                    <form method="POST" action="{{ route('nomina.pay') }}"
                          onsubmit="return confirm('¿Confirmar pago de la nómina de {{ $startDate->translatedFormat('F Y') }} por C$ {{ number_format($totals['net_salary'], 2) }}?');">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Pagar nómina
                        </button>
                    </form>
                @endunless
            @endif
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="card p-4 border-t-4 border-slate-400">
            <p class="text-xs uppercase tracking-wide text-slate-500">Salario base</p>
            <p class="mt-2 text-lg font-bold text-slate-800" data-payroll-total="base_salary">C$ {{ number_format($totals['base_salary'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-emerald-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Bonos</p>
            <p class="mt-2 text-lg font-bold text-emerald-700" data-payroll-total="bonuses">C$ {{ number_format($totals['bonuses'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-indigo-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Bruto</p>
            <p class="mt-2 text-lg font-bold text-indigo-700" data-payroll-total="gross_salary">C$ {{ number_format($totals['gross_salary'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-orange-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">INSS + IR</p>
            <p class="mt-2 text-lg font-bold text-orange-700" data-payroll-total="statutory_deductions">C$ {{ number_format($totals['inss_deduction'] + $totals['ir_deduction'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-rose-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total deducciones</p>
            <p class="mt-2 text-lg font-bold text-rose-700" data-payroll-total="total_deductions">C$ {{ number_format($totals['total_deductions'], 2) }}</p>
        </div>
        <div class="card p-4 border-t-4 border-violet-500">
            <p class="text-xs uppercase tracking-wide text-slate-500">Neto a pagar</p>
            <p class="mt-2 text-lg font-bold text-violet-700" data-payroll-total="net_salary">C$ {{ number_format($totals['net_salary'], 2) }}</p>
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



@push('scripts')
@include('planilla.partials.dynamic-payroll-charts', [
    'chartsUrl' => $chartsUrl,
    'initialChartData' => $initialChartData,
    'mode' => 'nomina',
    'monthInputId' => 'payrollMonthFilter',
])
@endpush
@endsection
