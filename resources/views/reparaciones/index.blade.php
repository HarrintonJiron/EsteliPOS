@extends('layouts.app')

@section('title', 'Dashboard de Reparaciones')

@section('content')
@php
    $activeFilters = collect([
        'search', 'status', 'priority', 'technician_id', 'device_brand',
        'payment_status', 'received_from', 'received_to', 'delivery_from',
        'delivery_to', 'overdue_only', 'date',
    ])->filter(fn ($key) => request()->filled($key) || ($key === 'overdue_only' && request()->boolean('overdue_only')))->count();
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-wrap justify-between items-start gap-3">
        <div>
            <h1 class="page-title">Dashboard de Reparaciones</h1>
            <p class="page-subtitle">Panel operativo del taller · entregas, estados y seguimiento</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reparaciones.view_expenses'))
                <a href="{{ route('reparaciones.gastos.index') }}" class="btn-secondary text-sm">Gastos operativos</a>
            @endif
            <a href="{{ route('reparaciones.create') }}" class="btn-primary text-sm">
                + Nueva orden
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="card p-3 bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
        <x-ui.stat-card label="Total órdenes" :value="$stats['total']" accent="#64748b" />
        <x-ui.stat-card label="Recibidos" :value="$stats['received']" accent="#3b82f6" value-class="text-blue-600" />
        <x-ui.stat-card label="En proceso" :value="$stats['in_repair']" accent="#6366f1" value-class="text-indigo-600" />
        <x-ui.stat-card label="Listos" :value="$stats['ready']" accent="#22c55e" value-class="text-green-600" />
        <x-ui.stat-card label="Entregados" :value="$stats['delivered']" accent="#10b981" value-class="text-emerald-600" />
        <x-ui.stat-card label="Entregan hoy" :value="$stats['due_today']" accent="#f59e0b" value-class="text-amber-600" />
        <x-ui.stat-card label="Atrasadas" :value="$stats['overdue']" accent="#ef4444" value-class="text-red-600" />
        <div class="kpi-card" style="--kpi-accent: #f43f5e">
            <p class="kpi-label">Gastos del mes</p>
            <p class="kpi-value text-rose-600 text-lg">C$ {{ number_format($expenseStats['month_total'] ?? 0, 0) }}</p>
            <p class="kpi-meta">{{ $expenseStats['month_count'] ?? 0 }} registros</p>
        </div>
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
                    placeholder="Orden, cliente, teléfono, equipo, IMEI, falla..."
                    class="input-field">
            </div>
            <div>
                <label class="form-label" for="status">Estado</label>
                <select id="status" name="status" class="select-field">
                    <option value="">Todos</option>
                    @foreach(['received' => 'Recibido', 'diagnosing' => 'Diagnóstico', 'waiting_parts' => 'Esp. repuestos', 'in_repair' => 'En reparación', 'ready' => 'Listo', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'] as $val => $label)
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
                <label class="form-label" for="technician_id">Técnico</label>
                <select id="technician_id" name="technician_id" class="select-field">
                    <option value="">Todos</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected((string) request('technician_id') === (string) $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="device_brand">Marca</label>
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
            <table class="w-full table-agro text-sm">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Cliente</th>
                        <th>Equipo</th>
                        <th>Falla</th>
                        <th>Recepción</th>
                        <th>Entrega est.</th>
                        <th class="text-center">Prioridad</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Pago</th>
                        <th class="text-right">Total</th>
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
                            <p class="text-xs text-slate-400">{{ $order->device_color ?: '—' }}{{ $order->device_imei ? ' · '.$order->device_imei : '' }}</p>
                        </td>
                        <td class="max-w-[10rem]">
                            <p class="text-slate-700 line-clamp-2">{{ $order->problem_description }}</p>
                        </td>
                        <td class="whitespace-nowrap">
                            <p class="font-medium text-slate-800">{{ $order->received_date->format('d/m/Y') }}</p>
                            @if($order->formattedReceivedTime())
                                <p class="text-xs text-slate-500">{{ $order->formattedReceivedTime() }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            @if($order->estimated_date || $order->estimated_delivery_time)
                                <p @class([
                                    'font-medium',
                                    'text-red-700' => $order->isEstimatedDeliveryOverdue(),
                                    'text-amber-700' => ! $order->isEstimatedDeliveryOverdue() && $order->isEstimatedDeliveryToday(),
                                    'text-slate-800' => ! $order->isEstimatedDeliveryOverdue() && ! $order->isEstimatedDeliveryToday(),
                                ])>{{ $order->estimatedDeliveryDisplay() }}</p>
                                @if($order->isEstimatedDeliveryOverdue())
                                    <span class="inline-block mt-0.5 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">Atrasada</span>
                                @elseif($order->isEstimatedDeliveryToday())
                                    <span class="inline-block mt-0.5 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Hoy</span>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $order->priorityColor() }}">
                                {{ $order->priorityLabel() }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $order->statusColor() }}">
                                {{ $order->statusLabel() }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="text-xs font-medium text-slate-600">{{ $order->paymentStatusLabel() }}</span>
                        </td>
                        <td class="text-right font-semibold text-slate-900 whitespace-nowrap">C$ {{ number_format($order->total, 2) }}</td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('reparaciones.show', $order->id) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Ver">Ver</a>
                                <a href="{{ route('reparaciones.ticket', $order->id) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Ticket">Ticket</a>
                                <a href="{{ route('reparaciones.edit', $order->id) }}" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Editar">Editar</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-12 text-slate-400">
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
