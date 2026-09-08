@extends('layouts.app')
@section('title', 'Dashboard Contable')
@section('content')
@php
    $cards = [
        ['Utilidad del mes', $profit],
        ['Ventas', $sales],
        ['Compras', $purchases],
        ['Ingresos contables', $income],
        ['Egresos contables', $expenses],
        ['Caja', $cash],
        ['Banco', $bank],
        ['Cuentas por cobrar', $receivables],
        ['Cuentas por pagar', $payables],
        ['Capital', $capital],
    ];
@endphp
<div class="ex-shell accounting-workspace">
    @include('contabilidad._tabs')

    <x-ui.command-hero
        kicker="Finanzas"
        title="Dashboard contable"
        :subtitle="'Resumen financiero de ' . $periodLabel"
        metric-label="Utilidad del mes"
        :metric-value="'C$ ' . number_format($profit, 0)"
        :stats="[
            ['label' => 'Ventas', 'value' => 'C$ ' . number_format($sales, 0)],
            ['label' => 'Compras', 'value' => 'C$ ' . number_format($purchases, 0)],
            ['label' => 'Caja', 'value' => 'C$ ' . number_format($cash, 0)],
        ]"
    >
        <x-slot:actions>
            <form method="GET" class="flex gap-2">
                <input type="month" name="month" value="{{ $month }}" class="input-field">
                <button class="ex-btn ex-btn--solid">Actualizar</button>
            </form>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="accounting-pulse">
        @foreach($cards as $index => [$label, $value])
            <article class="accounting-pulse__card accounting-pulse__card--{{ $index % 5 }}"><span class="accounting-pulse__label">{{ $label }}</span><strong>C$ {{ number_format($value, 0) }}</strong><span class="accounting-pulse__line"></span></article>
        @endforeach
    </div>

    <div class="ex-panel">
        <div class="ex-panel__head">
            <div>
                <h2>Tendencia de 12 meses</h2>
                <p>Ingresos, egresos y utilidad contable</p>
            </div>
        </div>
        <div class="ex-chart ex-chart--trend accounting-chart" style="height: 22rem">
            <canvas id="accountingChart"></canvas>
        </div>
    </div>

    <nav class="ex-shortcuts" style="grid-template-columns: repeat(3, minmax(0, 1fr))">
        <a href="{{ route('contabilidad.estado-resultados.index', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="ex-shortcut">
            <strong>Estado de resultados</strong>
            <span>Detalle de la utilidad del período</span>
        </a>
        <a href="{{ route('contabilidad.balance-general.index', ['as_of_date' => $dateTo]) }}" class="ex-shortcut">
            <strong>Balance general</strong>
            <span>Activos, pasivos y patrimonio</span>
        </a>
        <a href="{{ route('contabilidad.flujo-caja.index', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="ex-shortcut">
            <strong>Flujo de caja</strong>
            <span>Entradas y salidas de efectivo</span>
        </a>
    </nav>
</div>
@push('scripts')
<script>
const initializeAccountingChart = () => {
    const accountingData = @json($chart);
    new Chart(document.getElementById('accountingChart'), {
        type: 'line',
        data: {
            labels: accountingData.map(item => item.label),
            datasets: [
                { label: 'Ingresos', data: accountingData.map(item => item.income), borderColor: '#4f46e5', tension: .3 },
                { label: 'Egresos', data: accountingData.map(item => item.expenses), borderColor: '#e11d48', tension: .3 },
                { label: 'Utilidad', data: accountingData.map(item => item.profit), borderColor: '#059669', tension: .3 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: value => 'C$ ' + Number(value).toLocaleString('es-NI') } } },
        },
    });
};

if (window.Chart) {
    initializeAccountingChart();
} else {
    window.addEventListener('charts:ready', initializeAccountingChart, { once: true });
}
</script>
@endpush
@endsection
