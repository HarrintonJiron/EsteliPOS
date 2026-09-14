@extends('layouts.app')

@section('title', ($purchase->status === 'ordered' ? 'Proforma de compra #' : 'Compra #') . $purchase->id)

@section('content')
@php
    $statusLabel = $purchase->statusLabel();
    $statusClass = match ($purchase->status) {
        'ordered' => 'badge-info',
        'completed' => 'badge-success',
        'pending' => 'badge-warning',
        default => 'badge-danger',
    };
    $totals = $purchaseTotals;
    $lineRate = max((float) ($purchase->exchange_rate ?: 1), 0.000001);
    $canEditPurchase = auth()->user()?->isAdmin() || auth()->user()?->hasPermission('compras.edit');
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">
                        {{ $purchase->status === 'ordered' ? 'Proforma de compra' : 'Compra' }} #{{ $purchase->id }}
                    </p>
                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $purchase->supplier->name ?? 'Sin proveedor' }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $purchase->date?->format('d/m/Y') }} · {{ $purchase->details->count() }} productos · {{ $purchase->warehouse->name ?? 'Sin bodega' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                <a href="{{ route('compras.index') }}" class="btn-outline">Volver</a>
                @if($purchase->status === 'ordered')
                    <a href="{{ route('compras.proformas.ticket', $purchase->id) }}" target="_blank" class="btn-outline">Imprimir ticket</a>
                    <a href="{{ route('compras.proformas.pdf', $purchase->id) }}" target="_blank" class="btn-outline text-indigo-700">Imprimir PDF</a>
                @endif
                @if($purchase->status !== 'canceled')
                    <a href="{{ route('compras.edit', $purchase->id) }}" class="btn-outline">Editar</a>
                @endif
                @if($purchase->status !== 'ordered')
                    @include('compras._status_actions', ['purchase' => $purchase, 'compact' => false])
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Estado</p>
            <p class="mt-2 font-semibold text-slate-800">{{ $statusLabel }}</p>
            @if($purchase->status === 'ordered')
                <p class="mt-1 text-xs text-blue-700">Sin movimientos de inventario o contabilidad.</p>
            @elseif($purchase->status === 'pending')
                <p class="mt-1 text-xs text-amber-700">Mercadería en inventario; saldo pendiente.</p>
            @endif
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Bodega de destino</p>
            <p class="mt-2 font-semibold text-slate-800">{{ $purchase->warehouse->name ?? '—' }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total {{ $totals['document_currency'] }}</p>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ $totals['document_symbol'] }} {{ number_format($totals['document_total'], 2) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Equivalencia {{ $totals['equivalence_currency'] }}</p>
            <p class="mt-2 text-xl font-bold text-indigo-700">
                @if($totals['equivalence_total'] !== null)
                    {{ $totals['equivalence_symbol'] }} {{ number_format($totals['equivalence_total'], 2) }}
                @else
                    <span class="text-sm font-medium text-slate-400">Sin tipo de cambio</span>
                @endif
            </p>
        </div>
    </div>

    @if($purchase->status === 'ordered' && $canEditPurchase)
        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm">
            <div class="grid gap-5 p-5 lg:grid-cols-[1fr_auto] lg:items-end lg:p-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Recepción del pedido</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">Confirmar ingreso y forma de pago</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Confirma solamente cuando la mercadería haya llegado. En ese momento los productos entrarán a la bodega y se registrará la compra.
                    </p>
                </div>
                <form action="{{ route('compras.status', $purchase->id) }}" method="POST" class="grid gap-3 sm:grid-cols-[minmax(180px,1fr)_auto] sm:items-end" onsubmit="return confirm('¿Confirmar que la mercadería llegó? Las existencias ingresarán a la bodega seleccionada y se registrará la compra.')">
                    @csrf
                    <input type="hidden" name="status" value="received">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Forma de pago</span>
                        <select name="payment_type" class="select-field w-full" aria-label="Forma de pago al recibir">
                            <option value="credit" @selected($purchase->payment_type === 'credit')>A crédito</option>
                            <option value="cash" @selected($purchase->payment_type === 'cash')>Efectivo</option>
                            <option value="transfer" @selected($purchase->payment_type === 'transfer')>Transferencia</option>
                        </select>
                    </label>
                    <button type="submit" class="btn-primary whitespace-nowrap px-5 py-3">Confirmar ingreso</button>
                </form>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-blue-200/70 bg-white/60 px-5 py-3 lg:px-6">
                <p class="text-xs text-slate-500">Puedes editar o quitar productos mientras el pedido siga en proceso.</p>
                <form action="{{ route('compras.status', $purchase->id) }}" method="POST" onsubmit="return confirm('¿Anular este pedido en proceso? No se moverá inventario.')">
                    @csrf
                    <input type="hidden" name="status" value="canceled">
                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">Anular proforma</button>
                </form>
            </div>
        </section>
    @endif

    @if($invoiceTaxDisplay->showsTaxBreakdown())
        <div class="card px-4 py-3 text-sm text-slate-600">
            {{ $invoiceTaxDisplay->taxLabel((float) ($purchase->tax_rate ?? 0)) }}:
            <span class="font-semibold text-slate-800">{{ $totals['company_symbol'] }} {{ number_format($invoiceTaxDisplay->displayTaxAmount((float) ($purchase->tax_total ?? 0)), 2) }}</span>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-4 sm:px-5">
            <div>
                <h2 class="font-semibold text-slate-900">Detalle de productos</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $purchase->details->count() }} líneas en esta {{ $purchase->status === 'ordered' ? 'proforma' : 'compra' }}</p>
            </div>
            <p class="text-sm font-bold text-slate-900">Total: {{ $totals['document_symbol'] }} {{ number_format($totals['document_total'], 2) }}</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($purchase->details as $detail)
                @php
                    $lineDocument = (float) $detail->subtotal;
                    $lineCompany = $totals['is_foreign'] ? round($lineDocument * $lineRate, 2) : $lineDocument;
                    $lineEquivalence = $totals['is_foreign']
                        ? $lineCompany
                        : $purchaseCosting->toEquivalenceAmount($lineCompany, $purchase);
                @endphp
                <div class="grid gap-4 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-5">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $detail->product->name ?? 'N/A' }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $detail->product->code ?? 'Sin código' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 sm:justify-end">
                        <div class="text-left sm:text-right">
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
                        <div class="text-left sm:text-right">
                            <p class="text-xs text-slate-500">Costo unitario</p>
                            <p class="font-semibold text-slate-800">{{ $totals['document_symbol'] }} {{ number_format($detail->price, 2) }}</p>
                        </div>
                        <div class="min-w-24 text-left sm:text-right">
                            <p class="text-xs text-slate-500">Subtotal</p>
                            <p class="font-bold text-slate-900">{{ $totals['document_symbol'] }} {{ number_format($lineDocument, 2) }}</p>
                            @if($lineEquivalence !== null && $totals['document_currency'] !== $totals['equivalence_currency'])
                                <p class="text-[11px] text-indigo-600">{{ $totals['equivalence_symbol'] }} {{ number_format($lineEquivalence, 2) }}</p>
                            @endif
                        </div>
                        @if($purchase->status === 'ordered' && $canEditPurchase)
                            <form action="{{ route('compras.proformas.details.destroy', [$purchase->id, $detail->id]) }}" method="POST" onsubmit="return confirm('¿Quitar este producto de la proforma?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:bg-red-50" aria-label="Eliminar producto {{ $detail->product->name ?? '' }}">
                                    Eliminar producto
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
