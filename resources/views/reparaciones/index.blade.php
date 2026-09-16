@extends('layouts.app')

@section('title', 'Taller de Joyería')

@section('content')
<style>
    .repair-orders-table { table-layout: fixed; width: 100%; }
    .repair-orders-table th,
    .repair-orders-table td { padding: 0.4rem 0.2rem; vertical-align: top; overflow-wrap: anywhere; }
    .repair-orders-table .order-actions { white-space: nowrap; }
    @media (max-width: 1023px) {
        .repair-orders-table { min-width: 40rem; }
    }
</style>
@php
    $activeFilters = collect([
        'search', 'status', 'priority', 'technician_id', 'device_brand',
        'payment_status', 'received_from', 'received_to', 'delivery_from',
        'delivery_to', 'overdue_only', 'date',
    ])->filter(fn ($key) => request()->filled($key) || ($key === 'overdue_only' && request()->boolean('overdue_only')))->count();
@endphp

<div class="ex-shell">

    <x-ui.command-hero
        kicker="Taller"
        title="Taller de Joyería"
        subtitle="Recepción, reparación y entrega de joyas"
        metric-label="Órdenes activas"
        :metric-value="number_format($stats['in_repair'] + $stats['received'] + $stats['ready'])"
        :meta="[$stats['overdue'] . ' atrasadas', $stats['due_today'] . ' entregan hoy']"
        :stats="[
            ['label' => 'Listas', 'value' => number_format($stats['ready'])],
            ['label' => 'Atrasadas', 'value' => number_format($stats['overdue'])],
            ['label' => 'Gastos mes', 'value' => 'C$ ' . number_format($expenseStats['month_total'] ?? 0, 0)],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reparaciones.view_expenses'))
                <a href="{{ route('reparaciones.gastos.index') }}" class="ex-btn">Gastos operativos</a>
            @endif
            <a href="{{ route('reparaciones.create') }}" class="ex-btn ex-btn--solid">+ Nueva orden</a>
        </x-slot:actions>
    </x-ui.command-hero>

    @if(session('success'))
        <div class="card p-3 bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="ex-kpis ex-kpis--8">
        <x-ui.command-kpi label="Total órdenes" :value="number_format($stats['total'])" />
        <x-ui.command-kpi label="Recibidas" :value="number_format($stats['received'])" />
        <x-ui.command-kpi label="En proceso" :value="number_format($stats['in_repair'])" />
        <x-ui.command-kpi label="Listas" :value="number_format($stats['ready'])" />
        <x-ui.command-kpi label="Entregados" :value="number_format($stats['delivered'])" />
        <x-ui.command-kpi label="Entregan hoy" :value="number_format($stats['due_today'])" />
        <x-ui.command-kpi label="Atrasadas" :value="number_format($stats['overdue'])" />
        <x-ui.command-kpi label="Gastos del mes" :value="'C$ ' . number_format($expenseStats['month_total'] ?? 0, 0)" :meta="($expenseStats['month_count'] ?? 0) . ' registros'" />
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('reparaciones.index') }}" class="filter-panel">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div>
                <h2 class="font-semibold text-slate-800 text-sm">Filtros del tablero</h2>
                <p class="text-xs text-slate-500">{{ $filteredCount }} resultado(s){{ $activeFilters ? " · {$activeFilters} filtro(s) activo(s)" : '' }}</p>
            </div>
            <div class="flex gap-2">
                @if($activeFilters)
                    <a href="{{ route('reparaciones.index') }}" class="btn-outline text-xs py-1.5">Limpiar</a>
                @endif
                <button type="submit" class="btn-primary text-xs py-1.5">Aplicar filtros</button>
            </div>
        </div>

        <div class="filter-grid">
            <div class="sm:col-span-2">
                <label class="form-label" for="search">Búsqueda</label>
                <input type="search" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Orden, cliente, teléfono, tipo, material, peso, trabajo..."
                    class="input-field">
            </div>
            <div>
                <label class="form-label" for="status">Estado</label>
                <select id="status" name="status" class="select-field">
                    <option value="">Todos</option>
                    @foreach(['received' => 'Recibido', 'diagnosing' => 'Evaluación', 'waiting_parts' => 'Esp. materiales', 'in_repair' => 'En taller', 'ready' => 'Lista', 'delivered' => 'Entregada', 'cancelled' => 'Cancelada'] as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="priority">Prioridad</label>
                <select id="priority" name="priority" class="select-field">
                    <option value="">Todas</option>
                    @foreach(['urgent' => 'Urgente', 'high' => 'Alta', 'normal' => 'Normal', 'low' => 'Baja'] as $val => $label)
                        <option value="{{ $val }}" @selected(request('priority') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="technician_id">Joyero</label>
                <select id="technician_id" name="technician_id" class="select-field">
                    <option value="">Todos</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected((string) request('technician_id') === (string) $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="device_brand">Tipo de joya</label>
                <select id="device_brand" name="device_brand" class="select-field">
                    <option value="">Todas</option>
                    @foreach($deviceBrands as $brand)
                        <option value="{{ $brand }}" @selected(request('device_brand') === $brand)>{{ $brand }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="payment_status">Pago</label>
                <select id="payment_status" name="payment_status" class="select-field">
                    <option value="">Todos</option>
                    @foreach(['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'] as $val => $label)
                        <option value="{{ $val }}" @selected(request('payment_status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="received_from">Recepción desde</label>
                <input type="date" id="received_from" name="received_from" value="{{ request('received_from') }}" class="input-field">
            </div>
            <div>
                <label class="form-label" for="received_to">Recepción hasta</label>
                <input type="date" id="received_to" name="received_to" value="{{ request('received_to') }}" class="input-field">
            </div>
            <div>
                <label class="form-label" for="delivery_from">Entrega desde</label>
                <input type="date" id="delivery_from" name="delivery_from" value="{{ request('delivery_from') }}" class="input-field">
            </div>
            <div>
                <label class="form-label" for="delivery_to">Entrega hasta</label>
                <input type="date" id="delivery_to" name="delivery_to" value="{{ request('delivery_to') }}" class="input-field">
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="overdue_only" value="1" @checked(request()->boolean('overdue_only'))
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Solo entregas atrasadas
            </label>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('reparaciones.index', ['delivery_from' => now()->toDateString(), 'delivery_to' => now()->toDateString()]) }}"
                   class="rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100">Entregan hoy</a>
                <a href="{{ route('reparaciones.index', ['status' => 'ready']) }}"
                   class="rounded-full bg-green-50 px-3 py-1 font-semibold text-green-700 ring-1 ring-green-200 hover:bg-green-100">Listos para entregar</a>
                <a href="{{ route('reparaciones.index', ['overdue_only' => 1]) }}"
                   class="rounded-full bg-red-50 px-3 py-1 font-semibold text-red-700 ring-1 ring-red-200 hover:bg-red-100">Atrasadas</a>
            </div>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="repair-orders-table table-agro text-sm">
                <colgroup>
                    <col style="width: 6.5rem">
                    <col style="width: 10rem">
                    <col>
                    <col style="width: 10rem">
                    <col style="width: 7.5rem">
                    <col style="width: 6.5rem">
                    <col style="width: 7rem">
                </colgroup>
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Cliente</th>
                        <th>Joya y trabajo</th>
                        <th>Fechas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Importe</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr @class([
                        'bg-green-50/70' => $order->status === 'ready',
                        'bg-red-50/70' => $order->isEstimatedDeliveryOverdue(),
                        'bg-amber-50/60' => ! $order->isEstimatedDeliveryOverdue() && $order->isEstimatedDeliveryToday(),
                    ])>
                        <td>
                            <p class="font-mono font-bold text-indigo-700">{{ $order->order_number }}</p>
                            @if($order->technician)
                                <p class="text-[11px] text-slate-400">{{ $order->technician->name }}</p>
                            @endif
                        </td>
                        <td>
                            <p class="font-semibold text-slate-800">{{ $order->client_name }}</p>
                            <p class="text-xs text-slate-400">{{ $order->client_phone ?: '—' }}</p>
                        </td>
                        <td>
                            <p class="font-medium text-slate-800">{{ $order->device_brand }} {{ $order->device_model }}</p>
                            @if($order->device_color || $order->device_imei)
                                <p class="text-[11px] text-slate-500 truncate">{{ $order->device_color }}{{ $order->device_imei ? ' · '.$order->device_imei : '' }}</p>
                            @endif
                            <p class="mt-1 text-xs text-slate-600 line-clamp-2">{{ $order->problem_description }}</p>
                        </td>
                        <td class="text-xs">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Recepción</p>
                            <p class="font-medium text-slate-800 whitespace-nowrap">{{ $order->received_date->format('d/m/Y') }}</p>
                            @if($order->formattedReceivedTime())
                                <p class="text-[11px] text-slate-500">{{ $order->formattedReceivedTime() }}</p>
                            @endif
                            @if($order->estimated_date || $order->estimated_delivery_time)
                                <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Entrega</p>
                                <p @class([
                                    'font-medium whitespace-nowrap',
                                    'text-red-700' => $order->isEstimatedDeliveryOverdue(),
                                    'text-amber-700' => ! $order->isEstimatedDeliveryOverdue() && $order->isEstimatedDeliveryToday(),
                                    'text-slate-800' => ! $order->isEstimatedDeliveryOverdue() && ! $order->isEstimatedDeliveryToday(),
                                ])>{{ $order->estimatedDeliveryDisplay() }}</p>
                                @if($order->isEstimatedDeliveryOverdue())
                                    <span class="text-[10px] font-bold text-red-700">Atrasada</span>
                                @elseif($order->isEstimatedDeliveryToday())
                                    <span class="text-[10px] font-bold text-amber-700">Hoy</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $order->statusColor() }}">
                                {{ $order->statusLabel() }}
                            </span>
                            <p class="mt-1 text-[11px] font-medium text-slate-500">{{ $order->priorityLabel() }}</p>
                        </td>
                        <td class="text-right">
                            <p class="font-semibold text-slate-900 whitespace-nowrap">C$ {{ number_format($order->total, 2) }}</p>
                            <p class="text-[11px] font-medium text-slate-500">{{ $order->paymentStatusLabel() }}</p>
                        </td>
                        <td class="order-actions">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('reparaciones.show', $order->id) }}" aria-label="Ver orden" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700" title="Ver orden">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/></svg>
                                </a>
                                <a href="{{ route('reparaciones.ticket', $order->id) }}" target="_blank" aria-label="Imprimir ticket" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" title="Imprimir ticket">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                                </a>
                                <a href="{{ route('reparaciones.edit', $order->id) }}" aria-label="Editar orden" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700" title="Editar orden">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 19l-4 1 1-4L16.5 5.5a2.5 2.5 0 013.536 3.536L9 19z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-slate-400">
                            <p class="font-medium">No hay órdenes con los filtros seleccionados</p>
                            <p class="text-xs mt-1">Prueba ampliar el rango o crear una nueva orden</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">{{ $orders->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
