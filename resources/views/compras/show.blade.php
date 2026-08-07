@extends('layouts.app')

@section('title', 'Compra #' . $purchase->id)

@section('content')
@php
    $statusLabel = match ($purchase->status) {
        'completed' => 'Completada',
        'pending' => 'Pendiente',
        default => 'Anulada',
    };
    $statusClass = match ($purchase->status) {
        'completed' => 'badge-success',
        'pending' => 'badge-warning',
        default => 'badge-danger',
    };
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Compra #{{ $purchase->id }}</p>
            <h1 class="page-title">{{ $purchase->supplier->name ?? 'Sin proveedor' }}</h1>
            <p class="page-subtitle">{{ $purchase->date?->format('d/m/Y') }} · {{ $purchase->details->count() }} productos</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('compras.edit', $purchase->id) }}" class="btn-outline">Editar</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs text-slate-500">Estado</p>
            <p class="mt-2"><span class="{{ $statusClass }}">{{ $statusLabel }}</span></p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Bodega</p>
            <p class="mt-2 font-semibold text-slate-800">{{ $purchase->warehouse->name ?? '—' }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Subtotal</p>
            <p class="mt-2 font-semibold text-slate-800">C$ {{ number_format($purchase->subtotal ?? 0, 2) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Total</p>
            <p class="mt-2 text-xl font-bold text-indigo-700">C$ {{ number_format($purchase->total ?? 0, 2) }}</p>
        </div>
    </div>

    @if($invoiceTaxDisplay->showsTaxBreakdown())
        <div class="card px-4 py-3 text-sm text-slate-600">
            {{ $invoiceTaxDisplay->taxLabel((float) ($purchase->tax_rate ?? 0)) }}:
            <span class="font-semibold text-slate-800">C$ {{ number_format($invoiceTaxDisplay->displayTaxAmount((float) ($purchase->tax_total ?? 0)), 2) }}</span>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="font-semibold text-slate-800">Detalle de productos</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($purchase->details as $detail)
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900">{{ $detail->product->name ?? 'N/A' }}</p>
                        <p class="text-xs text-slate-500">{{ $detail->product->code ?? '' }}</p>
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Cantidad</p>
                            <p class="font-semibold text-slate-800">{{ $detail->quantity }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Costo</p>
                            <p class="font-semibold text-slate-800">C$ {{ number_format($detail->price, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Subtotal</p>
                            <p class="font-bold text-slate-900">C$ {{ number_format($detail->subtotal, 2) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
