@extends('layouts.app')

@section('title', 'Compras a proveedores')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Compras</h1>
            <p class="page-subtitle">Entradas de mercadería y control de costos por proveedor</p>
        </div>
        <a href="{{ route('compras.create') }}" class="btn-primary">+ Nueva compra</a>
    </div>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Este mes</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">C$ {{ number_format($stats['month_total'], 2) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Completadas</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($stats['completed_count']) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pendientes</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($stats['pending_count']) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total invertido</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600">C$ {{ number_format($stats['invested_total'], 2) }}</p>
        </div>
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
                    <option value="completed" @selected(request('status') === 'completed')>Completada</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
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
                        <th class="px-4 py-3 font-semibold text-right">Total</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $purchase)
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
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $purchase->document_number ?? 'COMP-' . str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $purchase->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $purchase->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $purchase->warehouse->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">C$ {{ number_format($purchase->total, 2) }}</td>
                            <td class="px-4 py-3"><span class="{{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('compras.show', $purchase->id) }}" class="text-indigo-600 hover:underline">Ver</a>
                                    <a href="{{ route('compras.edit', $purchase->id) }}" class="text-slate-600 hover:underline">Editar</a>
                                    <form action="{{ route('compras.destroy', $purchase->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta compra? Se revertirá el inventario.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">No hay compras registradas con estos filtros.</td>
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
