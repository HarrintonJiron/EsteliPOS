@extends('layouts.app')

@section('title', 'Recursos humanos')

@section('content')
@php
    $activeCount = $employees->where('is_active', true)->count();
    $avgScore = (int) round(collect($evaluations)->avg('score') ?? 0);
    $payroll = (float) $employees->sum('salary');
    $branchCount = $employees->pluck('branch_id')->filter()->unique()->count();
    $modules = [
        ['route' => 'rrhh.directory', 'title' => 'Directorio', 'text' => 'Ficha, sucursal y salario'],
        ['route' => 'rrhh.organigram', 'title' => 'Organigrama', 'text' => 'Puestos y cadena de mando'],
        ['route' => 'rrhh.attendance', 'title' => 'Asistencia', 'text' => 'Puntualidad de la quincena'],
        ['route' => 'rrhh.shifts', 'title' => 'Turnos', 'text' => 'Mostrador, bodega y taller'],
        ['route' => 'rrhh.thirteenth', 'title' => 'Aguinaldo', 'text' => '13° mes y vacaciones'],
        ['route' => 'rrhh.inss', 'title' => 'INSS / IR', 'text' => 'Retenciones de nómina'],
        ['route' => 'rrhh.evaluations', 'title' => 'Evaluaciones', 'text' => 'Desempeño del semestre'],
        ['route' => 'nomina.index', 'title' => 'Pagar nómina', 'text' => 'Cálculo y ticket de planilla'],
    ];
@endphp

<div class="ex-shell">
    @include('planilla._nav')

    <x-ui.command-hero
        kicker="Talento y nómina"
        title="Recursos humanos"
        :subtitle="($companyProfile['company_name'] ?? 'EsteliPOS') . ' · ' . $employees->count() . ' colaboradores en la red'"
        metric-label="Planilla mensual"
        :metric-value="'C$ ' . number_format($payroll, 0)"
        :meta="[$activeCount . ' activos', $branchCount . ' sucursales']"
        :stats="[
            ['label' => 'Activos', 'value' => (string) $activeCount],
            ['label' => 'Sucursales', 'value' => (string) $branchCount],
            ['label' => 'Eval. promedio', 'value' => $avgScore . '/100'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('nomina.index') }}" class="ex-btn ex-btn--solid">Pagar nómina</a>
            <a href="{{ route('planilla.index') }}" class="ex-btn">Dashboard planilla</a>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="ex-kpis ex-kpis--4">
        <x-ui.command-kpi label="Activos" :value="$activeCount" />
        <x-ui.command-kpi label="Planilla mensual" :value="'C$ ' . number_format($payroll, 0)" />
        <x-ui.command-kpi label="Sucursales" :value="$branchCount" />
        <x-ui.command-kpi label="Eval. promedio" :value="$avgScore . '/100'" />
    </div>

    <nav class="ex-shortcuts" aria-label="Módulos de recursos humanos">
        @foreach($modules as $card)
            <a href="{{ route($card['route']) }}" class="ex-shortcut">
                <strong>{{ $card['title'] }}</strong>
                <span>{{ $card['text'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
@endsection
