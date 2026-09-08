@extends('layouts.app')

@section('title', 'INSS e IR')

@section('content')
<div class="ex-shell">
    @include('planilla._nav')
    <x-ui.command-hero
        kicker="Nómina"
        title="INSS e IR"
        subtitle="Retenciones aplicadas en nóminas pagadas"
        metric-label="INSS retenido"
        :metric-value="'C$ ' . number_format($inss->sum('inss_deduction'), 0)"
        :compact="true"
    />

    <div class="hr-kpis" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <div class="hr-kpi">
            <p class="hr-kpi__label">INSS retenido</p>
            <p class="hr-kpi__value">C$ {{ number_format($inss->sum('inss_deduction'), 0) }}</p>
        </div>
        <div class="hr-kpi">
            <p class="hr-kpi__label">IR retenido</p>
            <p class="hr-kpi__value">C$ {{ number_format($inss->sum('ir_deduction'), 0) }}</p>
        </div>
        <div class="hr-kpi">
            <p class="hr-kpi__label">Nómina neta</p>
            <p class="hr-kpi__value">C$ {{ number_format($inss->sum('net_salary'), 0) }}</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="table-agro hr-table min-w-full">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Colaborador</th>
                    <th class="text-right">Bruto</th>
                    <th class="text-right">INSS</th>
                    <th class="text-right">IR</th>
                    <th class="text-right">Neto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inss as $row)
                    <tr>
                        <td>{{ $row->month }}/{{ $row->year }}</td>
                        <td class="font-medium">{{ $row->employee?->name ?? '—' }}</td>
                        <td class="text-right">C$ {{ number_format($row->gross_salary, 0) }}</td>
                        <td class="text-right">C$ {{ number_format($row->inss_deduction, 0) }}</td>
                        <td class="text-right">C$ {{ number_format($row->ir_deduction, 0) }}</td>
                        <td class="text-right font-semibold">C$ {{ number_format($row->net_salary, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-6">Pague una nómina para ver retenciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
