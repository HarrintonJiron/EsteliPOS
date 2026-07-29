@extends('layouts.app')

@section('title', 'Detalles de Crédito - ' . $client->name)

@section('content')

<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Encabezado --}}
    <div class="mb-8">
        <a href="{{ route('creditos.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold mb-4 inline-block">
            ← Volver
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ $client->name }}</h1>
        <p class="text-gray-600">{{ $client->business_name ?? 'N/A' }}</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="card p-5">
            <p class="text-xs text-slate-500 mb-1">Límite de Crédito</p>
            <p class="text-xl font-bold text-indigo-600">
                {{ $client->credit_limit > 0 ? 'C$ '.number_format($client->credit_limit, 2) : 'Ilimitado' }}
            </p>
            <p class="text-xs text-slate-400 mt-1">Plazo: {{ $client->credit_days ?? 30 }} días</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 mb-1">Deuda Total</p>
            <p class="text-xl font-bold text-slate-800">C$ {{ number_format($totalDebt, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 mb-1">Abonos</p>
            <p class="text-xl font-bold text-emerald-600">C$ {{ number_format($totalPaid, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 mb-1">Saldo Pendiente</p>
            <p class="text-xl font-bold {{ $balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">C$ {{ number_format($balance, 2) }}</p>
            @if($creditSummary['over_limit'] ?? false)<span class="badge-danger text-xs mt-1">Sobre límite</span>@endif
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 mb-1">Disponible</p>
            <p class="text-xl font-bold text-violet-600">
                @if($creditSummary['available_credit'] === null) Ilimitado
                @else C$ {{ number_format($creditSummary['available_credit'], 2) }} @endif
            </p>
            <p class="text-xs text-slate-400">Uso: {{ $creditSummary['usage_percent'] }}%</p>
        </div>
    </div>

    {{-- MORA CARD --}}
    @if($client->mora_enabled && $totalMora > 0)
    <div class="card p-5 mb-6 border border-red-200 bg-red-50">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="font-bold text-red-800 text-lg mb-1">
                    ⚠ Mora Acumulada: C$ {{ number_format($totalMora, 2) }}
                </h3>
                <p class="text-xs text-red-600 mb-3">
                    Tasa: {{ $client->mora_rate }}% diario
                    @if($client->mora_grace_days > 0) · {{ $client->mora_grace_days }} días de gracia @endif
                    @if((float)$client->mora_max_pct > 0) · Tope: {{ $client->mora_max_pct }}% del principal @endif
                </p>
                @if(count($moraBreakdown))
                <table class="text-xs w-full max-w-lg">
                    <thead><tr class="text-red-700 font-semibold">
                        <th class="text-left py-1 pr-3">Factura</th>
                        <th class="text-right pr-3">Principal</th>
                        <th class="text-right pr-3">Días venc.</th>
                        <th class="text-right pr-3">Días mora</th>
                        <th class="text-right">Mora</th>
                    </tr></thead>
                    <tbody>
                    @foreach($moraBreakdown as $row)
                    <tr class="border-t border-red-200">
                        <td class="py-1 pr-3 font-mono">{{ $row['invoice_number'] }}</td>
                        <td class="text-right pr-3">C$ {{ number_format($row['principal'], 2) }}</td>
                        <td class="text-right pr-3">{{ $row['days_late'] }}d</td>
                        <td class="text-right pr-3">{{ $row['billable_days'] }}d</td>
                        <td class="text-right font-bold text-red-700">C$ {{ number_format($row['mora'], 2) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            <div class="text-right shrink-0">
                <p class="text-3xl font-black text-red-700">C$ {{ number_format($totalMora, 2) }}</p>
                <p class="text-xs text-red-500 mt-1">Total + mora: C$ {{ number_format($balance + $totalMora, 2) }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-2 gap-6">
        <div class="col-span-2 text-right mb-4">
            <a href="{{ route('creditos.statement', $client->id) }}" class="btn-outline text-sm">Imprimir Estado de Cuenta</a>
        </div>

        {{-- Ventas a Crédito --}}
        <div class="bg-white rounded shadow overflow-hidden">
            <div class="bg-gray-100 border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-bold text-gray-900">Ventas a Crédito</h2>
            </div>

            @if($creditSales->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Factura</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Fecha</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Monto</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Vence</th>
                                @if($client->mora_enabled)<th class="px-4 py-2 text-right text-xs font-semibold text-red-600">Mora</th>@endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($creditSales as $sale)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-semibold text-blue-900">
                                        #{{ str_pad($sale->invoice_number, 6, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $sale->date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">
                                        C$ {{ number_format($sale->total, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <span class="inline-block px-2 py-1 text-xs rounded {{ $sale->due_date < now() ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $sale->due_date->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    @if($client->mora_enabled)
                                    @php
                                        $saleRow = collect($moraBreakdown)->firstWhere('invoice_number', $sale->invoice_number);
                                    @endphp
                                    <td class="px-4 py-3 text-sm text-right font-semibold {{ ($saleRow['mora'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                        {{ ($saleRow['mora'] ?? 0) > 0 ? 'C$ '.number_format($saleRow['mora'], 2) : '—' }}
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    Sin créditos pendientes
                </div>
            @endif
        </div>

        {{-- Abonos Recibidos --}}
        <div class="bg-white rounded shadow overflow-hidden">
            <div class="bg-gray-100 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Abonos Recibidos</h2>
                <a href="{{ route('creditos.create', $client->id) }}" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-4 py-2 rounded text-sm transition-colors">
                    ➕ Nuevo Abono
                </a>
            </div>

            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Tipo</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Monto</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Referencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($payments as $payment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $payment->payment_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded font-medium">
                                            {{ $payment->payment_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-green-700 text-right">
                                        C$ {{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $payment->reference_number ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    Sin abonos registrados
                </div>
            @endif
        </div>

    </div>

</div>

@endsection
