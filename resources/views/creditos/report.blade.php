@extends('layouts.app')

@section('title', 'Reporte de Créditos')

@section('content')

<div class="space-y-6">

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h2 class="page-title">Reporte Pro de Créditos</h2>
            <p class="page-subtitle">Cartera, antigüedad, límites y movimientos del período</p>
        </div>
        <a href="{{ route('creditos.export', request()->only(['start_date', 'end_date'])) }}" class="btn-outline text-sm">Exportar CSV</a>
    </div>

    <div class="flex gap-1 border-b border-slate-200">
        <a href="{{ route('creditos.index') }}" class="tab-link tab-link-inactive">Clientes con Deuda</a>
        <a href="{{ route('creditos.overdue') }}" class="tab-link tab-link-inactive">Vencidos</a>
        <a href="{{ route('creditos.report') }}" class="tab-link tab-link-active">Reporte Pro</a>
    </div>

    <form method="get" class="card p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Desde</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="input-field">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Hasta</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="input-field">
            </div>
            <button type="submit" class="btn-primary">Actualizar</button>
        </div>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-5 bg-gradient-to-br from-indigo-600 to-indigo-700 text-white">
            <p class="text-indigo-200 text-xs">Créditos del período</p>
            <p class="text-2xl font-bold">C$ {{ number_format($totalCredits, 2) }}</p>
            <p class="text-xs text-indigo-200">{{ $creditsSold->count() }} facturas</p>
        </div>
        <div class="card p-5 bg-gradient-to-br from-emerald-600 to-emerald-700 text-white">
            <p class="text-emerald-200 text-xs">Abonos del período</p>
            <p class="text-2xl font-bold">C$ {{ number_format($totalPayments, 2) }}</p>
            <p class="text-xs text-emerald-200">{{ $paymentsReceived->count() }} abonos</p>
        </div>
        <div class="card p-5 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Cartera total</p>
            <p class="text-2xl font-bold text-amber-600">C$ {{ number_format($portfolio['balance_total'], 2) }}</p>
        </div>
        <div class="card p-5 border-l-4 border-red-500">
            <p class="text-xs text-slate-500">Cartera vencida</p>
            <p class="text-2xl font-bold text-red-600">C$ {{ number_format($portfolio['overdue_total'], 2) }}</p>
        </div>
    </div>

    {{-- Antigüedad de cartera --}}
    <div class="card p-6">
        <h3 class="font-semibold text-slate-800 mb-4">Antigüedad de Cartera</h3>
        @php $agingMax = max(1, max($aging)); @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $agingStyles = [
                    'current' => ['label' => 'Al día', 'text' => 'text-emerald-600', 'bar' => 'bg-emerald-500'],
                    'days_1_30' => ['label' => '1-30 días', 'text' => 'text-amber-600', 'bar' => 'bg-amber-500'],
                    'days_31_60' => ['label' => '31-60 días', 'text' => 'text-orange-600', 'bar' => 'bg-orange-500'],
                    'days_60_plus' => ['label' => '+60 días', 'text' => 'text-red-600', 'bar' => 'bg-red-500'],
                ];
            @endphp
            @foreach($agingStyles as $key => $style)
            <div class="p-4 bg-slate-50 rounded-xl">
                <p class="text-xs text-slate-500">{{ $style['label'] }}</p>
                <p class="text-xl font-bold {{ $style['text'] }}">C$ {{ number_format($aging[$key], 2) }}</p>
                <div class="h-1.5 bg-slate-200 rounded-full mt-2 overflow-hidden">
                    <div class="h-full {{ $style['bar'] }}" style="width: {{ ($aging[$key] / $agingMax) * 100 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top deudores --}}
        <div class="card overflow-hidden">
            <div class="card-header"><h3 class="font-semibold text-slate-800">Mayores Deudores</h3></div>
            <div class="divide-y divide-slate-100">
                @forelse($topDebtors as $row)
                <div class="px-4 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-slate-800">{{ $row['client']->name }}</p>
                        <p class="text-xs text-slate-500">Límite: C$ {{ number_format($row['client']->credit_limit, 0) }}</p>
                    </div>
                    <p class="font-bold text-red-600">C$ {{ number_format($row['balance'], 2) }}</p>
                </div>
                @empty
                <p class="p-6 text-center text-slate-500">Sin deudores</p>
                @endforelse
            </div>
        </div>

        {{-- Sobre límite --}}
        <div class="card overflow-hidden">
            <div class="card-header"><h3 class="font-semibold text-slate-800">Clientes Sobre el Límite</h3></div>
            <div class="divide-y divide-slate-100">
                @forelse($clientsOverLimit as $row)
                <div class="px-4 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-slate-800">{{ $row['client']->name }}</p>
                        <p class="text-xs text-slate-500">Límite: C$ {{ number_format($row['credit_limit'], 2) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-red-600">C$ {{ number_format($row['balance'], 2) }}</p>
                        <span class="badge-danger text-xs">+{{ number_format($row['balance'] - $row['credit_limit'], 2) }}</span>
                    </div>
                </div>
                @empty
                <p class="p-6 text-center text-slate-500">Ningún cliente excede su límite</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Movimientos del período --}}
    <div class="card overflow-hidden">
        <div class="card-header"><h3 class="font-semibold text-slate-800">Créditos Otorgados (período)</h3></div>
        @if($creditsSold->count())
        <table class="table-agro">
            <thead><tr><th>Cliente</th><th>Factura</th><th>Fecha</th><th>Vence</th><th class="text-right">Monto</th></tr></thead>
            <tbody>
                @foreach($creditsSold as $sale)
                <tr>
                    <td>{{ $sale->client->name ?? 'N/A' }}</td>
                    <td class="font-mono text-indigo-600">#{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->date->format('d/m/Y') }}</td>
                    <td>{{ $sale->due_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-right font-semibold">C$ {{ number_format($sale->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="p-6 text-center text-slate-500">Sin créditos en el período</p>
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="card-header"><h3 class="font-semibold text-slate-800">Abonos Recibidos (período)</h3></div>
        @if($paymentsReceived->count())
        <table class="table-agro">
            <thead><tr><th>Cliente</th><th>Fecha</th><th>Tipo</th><th class="text-right">Monto</th><th>Referencia</th></tr></thead>
            <tbody>
                @foreach($paymentsReceived as $payment)
                <tr>
                    <td>{{ $payment->client->name ?? 'N/A' }}</td>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td><span class="badge-info">{{ $payment->payment_type }}</span></td>
                    <td class="text-right font-semibold text-emerald-600">C$ {{ number_format($payment->amount, 2) }}</td>
                    <td class="text-sm text-slate-500">{{ $payment->reference_number ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="p-6 text-center text-slate-500">Sin abonos en el período</p>
        @endif
    </div>

</div>
@endsection
