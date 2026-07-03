@extends('layouts.app')

@section('title', 'Cambio - Venta Completada')

@section('content')

<div class="h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-12 max-w-2xl w-full text-center">
        
        {{-- Icono de éxito --}}
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-indigo-100 rounded-full">
                <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        {{-- Mensaje de éxito --}}
        <h1 class="text-4xl font-bold text-slate-900 mb-2">¡Venta Completada!</h1>
        <p class="text-lg text-slate-600 mb-8">Factura #{{ str_pad($sale->invoice_number, 6, '0', STR_PAD_LEFT) }}</p>

        {{-- Sección de cambio --}}
        <div class="bg-indigo-50 rounded-xl p-8 mb-8 border-2 border-indigo-200">
            <p class="text-slate-700 text-lg mb-3">Cambio a entregar:</p>
            <p class="text-7xl font-black text-indigo-600" id="changeAmount">
                C$ {{ number_format($changeAmount, 2) }}
            </p>
        </div>

        {{-- Detalles de la venta --}}
        <div class="bg-slate-50 rounded-xl p-6 mb-8 text-left">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Resumen de Venta</h2>
            
            <div class="space-y-3 border-b border-slate-200 pb-4 mb-4">
                <div class="flex justify-between text-slate-700">
                    <span>Subtotal:</span>
                    <span class="font-semibold">C$ {{ number_format($sale->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-700">
                    <span>IVA (15%):</span>
                    <span class="font-semibold">C$ {{ number_format($sale->tax_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-slate-900">
                    <span>Total:</span>
                    <span>C$ {{ number_format($sale->total, 2) }}</span>
                </div>
            </div>

            <div class="flex justify-between items-center text-slate-700">
                <span>Método de Pago:</span>
                <span class="font-semibold">
                    @if($sale->payment_type == 'cash')
                        💰 Efectivo
                    @elseif($sale->payment_type == 'card')
                        💳 Tarjeta
                    @elseif($sale->payment_type == 'transfer')
                        🏦 Transferencia/Tarjeta
                    @elseif($sale->payment_type == 'credit')
                        📋 Crédito
                    @endif
                </span>
            </div>
        </div>

        {{-- Cliente --}}
        <div class="mb-8 text-left bg-slate-100 rounded-xl p-4">
            <p class="text-sm text-slate-600">Cliente:</p>
            <p class="text-lg font-semibold text-slate-900">{{ $sale->billing_name }}</p>
            @if($sale->billing_ruc)
                <p class="text-sm text-slate-600">RUC: {{ $sale->billing_ruc }}</p>
            @endif
        </div>

        {{-- Botones de acción --}}
        <div class="space-y-3">
            <a 
                href="{{ route('facturacion.receipt', $sale->id) }}?change={{ $changeAmount }}"
                target="_blank"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition-colors block text-center">
                🖨️ Imprimir Recibo Térmico
            </a>
            
            <a 
                href="{{ route('facturacion.pos') }}"
                class="w-full bg-slate-600 hover:bg-slate-700 text-white font-bold py-3 rounded-xl transition-colors block text-center">
                ➕ Nueva Venta
            </a>

            <a 
                href="{{ route('facturacion.index') }}"
                class="w-full bg-slate-400 hover:bg-slate-500 text-white font-bold py-3 rounded-xl transition-colors block text-center">
                📋 Ver Ventas
            </a>
        </div>

        {{-- Información adicional --}}
        <div class="mt-8 text-sm text-slate-500 border-t border-slate-200 pt-4">
            <p>Hora: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Cajero: {{ auth()->user()->name ?? 'Sistema' }}</p>
        </div>

    </div>
</div>

<style>
    @media print {
        body { display: none; }
    }
</style>

@endsection
