@extends('layouts.app')

@section('title', 'Facturación')

@section('content')

<div class="ex-shell">

    <x-ui.command-hero
        kicker="Ventas"
        title="Facturación"
        subtitle="Administra las ventas realizadas desde el POS"
        metric-label="Ventas del mes"
        :metric-value="'C$ ' . number_format($stats['month_total'], 0)"
        :meta="[$stats['month_count'] . ' facturas']"
        :stats="[
            ['label' => 'Hoy', 'value' => 'C$ ' . number_format($stats['today_total'], 0)],
            ['label' => 'Tickets', 'value' => number_format($stats['month_count'])],
            ['label' => 'Pendientes', 'value' => number_format($stats['pending'])],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('facturacion.pos') }}" class="ex-btn ex-btn--solid">Nueva venta (POS)</a>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="ex-kpis ex-kpis--4">
        <x-ui.command-kpi label="Hoy" :value="'C$ ' . number_format($stats['today_total'], 0)" />
        <x-ui.command-kpi label="Mes" :value="'C$ ' . number_format($stats['month_total'], 0)" />
        <x-ui.command-kpi label="Facturas" :value="number_format($stats['month_count'])" />
        <x-ui.command-kpi label="Pendientes" :value="number_format($stats['pending'])" />
    </div>

    <div class="card p-4">
        <form method="GET" action="{{ route('facturacion.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <input type="text" name="search" placeholder="Cliente o factura..." value="{{ request('search') }}" class="input-field">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Fecha</label>
                <input type="date" name="date" value="{{ request('date') }}" class="input-field">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Estado</label>
                <select name="status" class="select-field">
                    <option value="">Todos</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Pagada</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>
            <button type="submit" class="btn-secondary">Filtrar</button>
            <a href="{{ route('facturacion.index') }}" class="btn-outline">Limpiar</a>
        </form>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th># Factura</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td class="font-semibold text-indigo-600">{{ $sale->invoice_number ?? str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $sale->client->name ?? $sale->billing_name ?? 'N/A' }}</td>
                    <td>{{ $sale->date ? $sale->date->format('d/m/Y') : 'N/A' }}</td>
                    <td>
                        @php
                            $methods = ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'credit' => 'Crédito'];
                        @endphp
                        <span class="badge-info">{{ $methods[$sale->payment_type] ?? ucfirst($sale->payment_type) }}</span>
                    </td>
                    <td class="font-semibold">C$ {{ number_format($sale->total, 2) }}</td>
                    <td>
                        <span class="{{ $sale->status === 'completed' ? 'badge-success' : ($sale->status === 'canceled' ? 'badge-danger' : 'badge-warning') }}">
                            {{ $sale->status === 'completed' ? 'Pagada' : ($sale->status === 'canceled' ? 'Anulada' : 'Pendiente') }}
                        </span>
                    </td>
                    <td class="text-center space-x-2">
                        <a href="{{ route('facturacion.show', $sale->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Ver</a>
                        <a href="{{ route('facturacion.receipt', $sale->id) }}" target="_blank" class="text-slate-600 hover:text-slate-800 text-sm">Recibo</a>
                        @if($sale->status !== 'canceled')
                        <a href="{{ route('facturacion.edit', $sale->id) }}" class="text-amber-600 hover:text-amber-800 text-sm">Editar</a>
                        @endif
                        @if(auth()->user()?->isAdmin() && $sale->status !== 'canceled')
                        <form action="{{ route('facturacion.destroy', $sale->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Anular esta factura? Se conservará el documento y se revertirán el inventario y el asiento contable.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Anular</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-8 text-slate-500">No hay facturas registradas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $sales->links() }}

</div>

@endsection
