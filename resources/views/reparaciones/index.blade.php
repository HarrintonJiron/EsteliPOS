@extends('layouts.app')

@section('title', 'Reparaciones')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center gap-3 flex-wrap">
        <div>
            <h1 class="page-title">Taller de Reparaciones</h1>
            <p class="page-subtitle">Gestión de órdenes de reparación de celulares y dispositivos</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reparaciones.view_expenses'))
                <a href="{{ route('reparaciones.gastos.index') }}" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5S13.657 14 12 14m0-6V6m0 8v2m8-4a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                    Gastos Operativos
                </a>
            @endif
            <a href="{{ route('reparaciones.create') }}"
               class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Orden
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="card p-4 bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="card p-4 border-l-4 border-slate-400">
            <p class="text-xs text-slate-500">Total</p>
            <p class="text-2xl font-bold text-slate-700">{{ $stats['total'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-blue-500">
            <p class="text-xs text-slate-500">Recibidos</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['received'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">En proceso</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['in_repair'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-green-500">
            <p class="text-xs text-slate-500">Listos</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['ready'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-slate-500">Entregados</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['delivered'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-rose-500">
            <p class="text-xs text-slate-500">Gastos del mes</p>
            <p class="text-lg font-bold text-rose-600">C$ {{ number_format($expenseStats['month_total'] ?? 0, 2) }}</p>
            <p class="text-xs text-slate-400">{{ $expenseStats['month_count'] ?? 0 }} registros</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar por orden, cliente, equipo, IMEI..."
                class="input-field flex-1 min-w-48">
            <select name="status" class="select-field w-44">
                <option value="">Todos los estados</option>
                @foreach(['received' => 'Recibido', 'diagnosing' => 'Diagnóstico', 'waiting_parts' => 'Esp. Repuestos', 'in_repair' => 'En Reparación', 'ready' => 'Listo', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" class="select-field w-36">
                <option value="">Prioridad</option>
                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>super urgente </option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Alta</option>
                <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Baja</option>
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="input-field w-40">
            <button type="submit" class="btn-secondary">Filtrar</button>
            @if(request()->hasAny(['search','status','priority','date']))
                <a href="{{ route('reparaciones.index') }}" class="btn-outline">Limpiar</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="card overflow-x-auto">
        <table class="w-full min-w-[1180px] table-agro">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Falla reportada</th>
                    <th class="text-center">Prioridad</th>
                    <th class="text-center">Entrega estimada</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr class="{{ $order->status === 'ready' ? 'bg-green-50' : ($order->priority === 'urgent' ? 'bg-red-50' : '') }}">
                    <td>
                        <p class="font-mono font-bold text-indigo-700">{{ $order->order_number }}</p>
                        <p class="text-xs text-slate-400">{{ $order->received_date->format('d/m/Y') }}{{ $order->received_time ? ' · '.substr($order->received_time, 0, 5) : '' }}</p>
                    </td>
                    <td>
                        <p class="font-semibold text-slate-800">{{ $order->client_name }}</p>
                        <p class="text-xs text-slate-400">{{ $order->client_phone }}</p>
                    </td>
                    <td>
                        <p class="font-semibold text-slate-800">{{ $order->device_brand }} {{ $order->device_model }}</p>
                        <p class="text-xs text-slate-400">{{ $order->device_color }}{{ $order->device_imei ? ' · IMEI: '.$order->device_imei : '' }}</p>
                    </td>
                    <td class="max-w-xs">
                        <p class="text-sm text-slate-700 line-clamp-2">{{ $order->problem_description }}</p>
                    </td>
                    <td class="text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $order->priorityColor() }}">
                            {{ $order->priorityLabel() }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($order->estimated_date)
                            <p class="text-sm font-semibold text-slate-700">{{ $order->estimated_date->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-500">Hora: {{ $order->estimated_time ? substr($order->estimated_time, 0, 5) : 'pendiente' }}</p>
                        @else
                            <p class="text-xs text-slate-400">Sin fecha ni hora</p>
                        @endif
                        <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $order->scheduleColor() }}">{{ $order->scheduleLabel() }}</span>
                    </td>
                    <td class="text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $order->statusColor() }}">
                            {{ $order->statusLabel() }}
                        </span>
                    </td>
                    <td class="text-right font-semibold text-slate-900">C$ {{ number_format($order->total, 2) }}</td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('reparaciones.show', $order->id) }}"
                               class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Ver">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('reparaciones.ticket', $order->id) }}" target="_blank"
                               class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Ticket">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </a>
                            <a href="{{ route('reparaciones.edit', $order->id) }}"
                               class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-12 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <p>No hay órdenes de reparación</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
