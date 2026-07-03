@extends('layouts.app')

@section('title', 'Créditos Vencidos')

@section('content')

<div class="space-y-6">

    <div>
        <h2 class="page-title">Créditos Vencidos</h2>
        <p class="page-subtitle">Cuentas con fechas de vencimiento expiradas</p>
    </div>

    <div class="flex gap-1 border-b border-slate-200">
        <a href="{{ route('creditos.index') }}" class="tab-link tab-link-inactive">Clientes con Crédito</a>
        <a href="{{ route('creditos.overdue') }}" class="tab-link tab-link-active">Créditos Vencidos</a>
        <a href="{{ route('creditos.report') }}" class="tab-link tab-link-inactive">Reporte General</a>
    </div>

    <div class="bg-red-50 border border-red-200 p-4 rounded-xl">
        <p class="text-red-800 font-medium text-sm">Estos créditos están vencidos y requieren gestión inmediata</p>
    </div>

    <div class="card overflow-hidden">
        @if($overdueCredits->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Cliente</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Factura</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fecha Venta</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Vencimiento</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Monto</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Saldo</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Días Vencido</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($overdueCredits as $sale)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $sale->client->name }}</p>
                                    <p class="text-xs text-gray-600">{{ $sale->client->ruc ?? 'N/A' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-blue-900">#{{ str_pad($sale->invoice_number, 6, '0', STR_PAD_LEFT) }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ $sale->date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-sm font-semibold rounded">
                                        {{ $sale->due_date->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                    C$ {{ number_format($sale->total, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-red-700">
                                    C$ {{ number_format($sale->balance, 2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block px-3 py-1 bg-orange-100 text-orange-700 text-sm font-bold rounded">
                                        {{ now()->diffInDays($sale->due_date) }} días
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center space-x-2">
                                    <a href="{{ route('creditos.show', $sale->client->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">
                                        Ver
                                    </a>
                                    <a href="{{ route('creditos.create', $sale->client->id) }}" class="text-green-700 hover:text-green-800 font-semibold text-sm">
                                        Abono
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($overdueCredits->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $overdueCredits->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-12 text-center text-gray-500">
                <p class="text-lg">✓ No hay créditos vencidos</p>
                <p class="text-sm">Todos los créditos están dentro de su fecha de vencimiento</p>
            </div>
        @endif
    </div>

</div>

@endsection
