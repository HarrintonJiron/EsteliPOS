@extends('layouts.app')

@section('title', 'Compra #' . $purchase->id)

@section('content')
@php
    $statusLabel = $purchase->statusLabel();
    $statusClass = match ($purchase->status) {
        'completed' => 'badge-success',
        'pending' => 'badge-warning',
        default => 'badge-danger',
    };
    $totals = $purchaseTotals;
    $lineRate = max((float) ($purchase->exchange_rate ?: 1), 0.000001);
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Compra #{{ $purchase->id }}</p>
            <h1 class="page-title">{{ $purchase->supplier->name ?? 'Sin proveedor' }}</h1>
            <p class="page-subtitle">
                {{ $purchase->date?->format('d/m/Y') }} · {{ $purchase->details->count() }} productos · {{ $totals['document_currency'] }}
                @if($totals['is_foreign'])
                    · T.C. {{ number_format($totals['exchange_rate'], 4) }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('compras.index') }}" class="btn-outline">Volver</a>
            @if($purchase->status !== 'canceled')
                <a href="{{ route('compras.edit', $purchase->id) }}" class="btn-outline">Editar</a>
            @endif
            @include('compras._status_actions', ['purchase' => $purchase, 'compact' => false])
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs text-slate-500">Estado</p>
            <p class="mt-2"><span class="{{ $statusClass }}">{{ $statusLabel }}</span></p>
            @if($purchase->status === 'pending')
                <p class="mt-2 text-xs text-amber-700">Mercadería en inventario. Saldo a crédito con el proveedor.</p>
            @elseif($purchase->status === 'canceled')
                <p class="mt-2 text-xs text-slate-500">No afecta inventario ni cuentas.</p>
            @elseif($purchase->payment_type === 'transfer')
                <p class="mt-2 text-xs text-slate-500">Pagada por transferencia.</p>
            @else
                <p class="mt-2 text-xs text-slate-500">Pagada de contado.</p>
            @endif
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Bodega</p>
            <p class="mt-2 font-semibold text-slate-800">{{ $purchase->warehouse->name ?? '—' }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Costo ({{ $totals['document_currency'] }})</p>
            <p class="mt-2 font-semibold text-slate-800">
                {{ $totals['document_symbol'] }} {{ number_format($totals['document_total'], 2) }}
            </p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Equivalencia {{ $totals['equivalence_currency'] }}</p>
            <p class="mt-2 text-xl font-bold text-indigo-700">
                @if($totals['equivalence_total'] !== null)
                    {{ $totals['equivalence_symbol'] }} {{ number_format($totals['equivalence_total'], 2) }}
                @else
                    <span class="text-sm font-medium text-slate-400">Sin tipo de cambio</span>
                @endif
            </p>
        </div>
    </div>

    @if($invoiceTaxDisplay->showsTaxBreakdown())
        <div class="card px-4 py-3 text-sm text-slate-600">
            {{ $invoiceTaxDisplay->taxLabel((float) ($purchase->tax_rate ?? 0)) }}:
            <span class="font-semibold text-slate-800">{{ $totals['company_symbol'] }} {{ number_format($invoiceTaxDisplay->displayTaxAmount((float) ($purchase->tax_total ?? 0)), 2) }}</span>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="font-semibold text-slate-800">Detalle de productos</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($purchase->details as $detail)
                @php
                    $lineDocument = (float) $detail->subtotal;
                    $lineCompany = $totals['is_foreign']
                        ? round($lineDocument * $lineRate, 2)
                        : $lineDocument;
                    $lineEquivalence = $totals['is_foreign']
                        ? $lineCompany
                        : $purchaseCosting->toEquivalenceAmount($lineCompany, $purchase);
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900">{{ $detail->product->name ?? 'N/A' }}</p>
                        <p class="text-xs text-slate-500">{{ $detail->product->code ?? '' }}</p>
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Cantidad</p>
                            <p class="font-semibold text-slate-800">
                                {{ rtrim(rtrim(number_format((float) $detail->quantity, 4, '.', ''), '0'), '.') }}
                                {{ $detail->unit?->abbreviation ?? $detail->product?->baseUnit?->abbreviation ?? '' }}
                            </p>
                            @if($detail->base_quantity && (float) $detail->base_quantity !== (float) $detail->quantity)
                                <p class="text-[11px] text-slate-400">
                                    = {{ rtrim(rtrim(number_format((float) $detail->base_quantity, 4, '.', ''), '0'), '.') }}
                                    {{ $detail->product?->baseUnit?->abbreviation ?? 'base' }}
                                </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Costo</p>
                            <p class="font-semibold text-slate-800">{{ $totals['document_symbol'] }} {{ number_format($detail->price, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Subtotal</p>
                            <p class="font-bold text-slate-900">{{ $totals['document_symbol'] }} {{ number_format($lineDocument, 2) }}</p>
                            @if($lineEquivalence !== null && $totals['document_currency'] !== $totals['equivalence_currency'])
                                <p class="text-[11px] text-indigo-600">
                                    {{ $totals['equivalence_symbol'] }} {{ number_format($lineEquivalence, 2) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
