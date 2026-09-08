@extends('layouts.app')

@section('title', 'Compras a proveedores')

@section('content')
<div class="ex-shell">
    <x-ui.command-hero
        kicker="Abastecimiento"
        title="Compras"
        subtitle="La mercadería entra al inventario; el pago puede ser de contado o a crédito"
        metric-label="Este mes"
        :metric-value="$companySymbol . ' ' . number_format($stats['month_total'], 0)"
        :meta="[$stats['completed_count'] . ' pagadas', $stats['pending_count'] . ' por pagar', $stats['ordered_count'] . ' pedidos']"
        :stats="[
            ['label' => 'Pagadas', 'value' => number_format($stats['completed_count'])],
            ['label' => 'Por pagar', 'value' => number_format($stats['pending_count'])],
            ['label' => 'Invertido', 'value' => $companySymbol . ' ' . number_format($stats['invested_total'], 0)],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('compras.proformas.create') }}" class="ex-btn">Proforma compras</a>
            <a href="{{ route('compras.create') }}" class="ex-btn ex-btn--solid">+ Nueva compra</a>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="ex-kpis ex-kpis--4">
        <x-ui.command-kpi label="Este mes" :value="$companySymbol . ' ' . number_format($stats['month_total'], 0)" />
        <x-ui.command-kpi label="Pagadas" :value="number_format($stats['completed_count'])" />
        <x-ui.command-kpi label="Por pagar" :value="number_format($stats['pending_count'])" />
        <x-ui.command-kpi label="Pedidos en proceso" :value="number_format($stats['ordered_count'])" />
        <x-ui.command-kpi label="Total invertido" :value="$companySymbol . ' ' . number_format($stats['invested_total'], 0)" />
    </div>

    <div class="card p-4">
        <form method="GET" action="{{ route('compras.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5 md:items-end">
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-500">Proveedor</label>
                <select name="supplier_id" class="select-field">
                    <option value="">Todos</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-field">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-field">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Estado</label>
                <select name="status" class="select-field">
                    <option value="">Todos</option>
                    <option value="completed" @selected(request('status') === 'completed')>Pagada</option>
                    <option value="pending" @selected(request('status') === 'pending')>Por pagar</option>
                    <option value="ordered" @selected(request('status') === 'ordered')>Pedido en proceso</option>
                    <option value="canceled" @selected(request('status') === 'canceled')>Anulada</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-5 md:justify-end">
                <a href="{{ route('compras.index') }}" class="btn-outline">Limpiar</a>
                <button type="submit" class="btn-primary">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Documento</th>
                        <th class="px-4 py-3 font-semibold">Proveedor</th>
                        <th class="px-4 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 font-semibold">Bodega</th>
                        <th class="px-4 py-3 font-semibold text-right">Costo</th>
                        <th class="px-4 py-3 font-semibold text-right">Equivalencia</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $purchase)
                        @php
                            $statusLabel = $purchase->statusLabel();
                            $statusClass = match ($purchase->status) {
                                'ordered' => 'badge-info',
                                'completed' => 'badge-success',
                                'pending' => 'badge-warning',
                                default => 'badge-danger',
                            };
                            $totals = $purchaseCosting->presentTotals($purchase);
                        @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $purchase->document_number ?? 'COMP-' . str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}
                                <p class="mt-0.5 text-[11px] font-normal text-slate-400">{{ $totals['document_currency'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $purchase->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $purchase->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $purchase->warehouse->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                {{ $totals['document_symbol'] }} {{ number_format($totals['document_total'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($totals['equivalence_total'] !== null)
                                    <p class="font-semibold text-slate-700">
                                        {{ $totals['equivalence_symbol'] }} {{ number_format($totals['equivalence_total'], 2) }}
                                    </p>
                                    <p class="text-[11px] font-normal text-slate-400">{{ $totals['equivalence_currency'] }}</p>
                                @else
                                    <span class="text-xs font-normal text-slate-400">Sin T.C. {{ $equivalenceCurrency }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><span class="{{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('compras.show', $purchase->id) }}" class="text-indigo-600 hover:underline">Ver</a>
                                    @if($purchase->status !== 'canceled')
                                        <a href="{{ route('compras.edit', $purchase->id) }}" class="text-slate-600 hover:underline">Editar</a>
                                    @endif
                                    @include('compras._status_actions', ['purchase' => $purchase, 'compact' => true])
                                    <form action="{{ route('compras.destroy', $purchase->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta compra? Se revertirá el inventario si la mercadería ya había ingresado.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">No hay compras registradas con estos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
