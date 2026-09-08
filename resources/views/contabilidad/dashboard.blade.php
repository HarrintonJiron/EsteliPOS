@extends('layouts.app')
@section('title', 'Dashboard Contable')
@section('content')
<div class="space-y-6">
@include('contabilidad._tabs')
<div class="flex flex-wrap justify-between items-end gap-4"><div><h1 class="page-title">Dashboard Contable</h1><p class="page-subtitle">Resumen financiero de {{ $periodLabel }}</p></div><form method="GET" class="flex gap-2"><input type="month" name="month" value="{{ $month }}" class="input-field"><button class="btn-primary">Actualizar</button></form></div>
@php $cards=[['Utilidad del mes',$profit,'emerald'],['Ventas',$sales,'blue'],['Compras',$purchases,'amber'],['Ingresos contables',$income,'indigo'],['Egresos contables',$expenses,'rose'],['Caja',$cash,'cyan'],['Banco',$bank,'sky'],['Cuentas por cobrar',$receivables,'violet'],['Cuentas por pagar',$payables,'orange'],['Capital',$capital,'slate']]; @endphp
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">@foreach($cards as [$label,$value,$color])<div class="card p-4 border-t-4 border-{{ $color }}-500"><p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-xl font-bold {{ $value < 0 ? 'text-red-600' : 'text-slate-800' }}">C$ {{ number_format($value,2) }}</p></div>@endforeach</div>
<div class="card p-5"><div class="flex justify-between items-center mb-4"><div><h2 class="font-semibold text-slate-800">Tendencia de 12 meses</h2><p class="text-xs text-slate-500">Ingresos, egresos y utilidad contable</p></div><div class="flex gap-4 text-xs"><span class="text-indigo-600">● Ingresos</span><span class="text-rose-600">● Egresos</span><span class="text-emerald-600">● Utilidad</span></div></div><div class="h-80"><canvas id="accountingChart"></canvas></div></div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4"><a href="{{ route('contabilidad.estado-resultados.index',['date_from'=>$dateFrom,'date_to'=>$dateTo]) }}" class="card p-4 hover:border-indigo-400"><strong>Estado de Resultados</strong><p class="text-sm text-slate-500">Detalle de la utilidad del período</p></a><a href="{{ route('contabilidad.balance-general.index',['as_of_date'=>$dateTo]) }}" class="card p-4 hover:border-indigo-400"><strong>Balance General</strong><p class="text-sm text-slate-500">Activos, pasivos y patrimonio</p></a><a href="{{ route('contabilidad.flujo-caja.index',['date_from'=>$dateFrom,'date_to'=>$dateTo]) }}" class="card p-4 hover:border-indigo-400"><strong>Flujo de Caja</strong><p class="text-sm text-slate-500">Entradas y salidas de efectivo</p></a></div>
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
