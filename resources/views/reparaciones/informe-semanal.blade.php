@extends('layouts.app')

@section('title', 'Informe Semanal de Reparaciones')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Informe Semanal de Reparaciones</h1>
            <p class="page-subtitle">Solo reparaciones ya <strong>entregadas</strong> en la semana, por fecha de entrega</p>
        </div>
        <a href="{{ route('reparaciones.index') }}" class="btn-outline">← Reparaciones</a>
    </div>

    <div class="card p-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('reparaciones.informe-semanal', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" class="btn-outline">← Semana anterior</a>
        <div class="text-center">
            <p class="text-sm font-semibold text-slate-700">{{ $weekStart->format('d/m/Y') }} — {{ $weekEnd->format('d/m/Y') }}</p>
            <a href="{{ route('reparaciones.informe-semanal') }}" class="text-xs text-indigo-600 hover:underline">Ir a esta semana</a>
        </div>
        <a href="{{ route('reparaciones.informe-semanal', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" class="btn-outline">Semana siguiente →</a>
    </div>

    @if($pendingInWindow > 0)
        <div class="card p-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm">
            Hay <strong>{{ $pendingInWindow }}</strong> orden(es) recibidas esta semana que aún no están entregadas —
            no aparecen en este informe hasta que se marquen como "Entregado".
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4 border-l-4 border-slate-400">
            <p class="text-xs text-slate-500">Entregadas</p>
            <p class="text-2xl font-bold text-slate-700">{{ $summary['count'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-slate-500">Total facturado</p>
            <p class="text-2xl font-bold text-emerald-600">@money($summary['total'], 2)</p>
        </div>
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">Cobrado</p>
            <p class="text-2xl font-bold text-indigo-600">@money($summary['collected_total'], 2)</p>
        </div>
        <div class="card p-4 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Saldo pendiente</p>
            <p class="text-2xl font-bold text-amber-600">@money($summary['balance_total'], 2)</p>
        </div>
        <div class="card p-4 border-l-4 border-slate-400">
            <p class="text-xs text-slate-500">Mano de obra</p>
            <p class="text-2xl font-bold text-slate-700">@money($summary['labor_total'], 2)</p>
        </div>
        <div class="card p-4 border-l-4 border-slate-400">
            <p class="text-xs text-slate-500">Repuestos</p>
            <p class="text-2xl font-bold text-slate-700">@money($summary['parts_total'], 2)</p>
        </div>
        <div class="card p-4 border-l-4 border-slate-400 col-span-2 md:col-span-2">
            <p class="text-xs text-slate-500">Tiempo promedio de entrega</p>
            <p class="text-2xl font-bold text-slate-700">{{ $summary['avg_turnaround_days'] !== null ? $summary['avg_turnaround_days'].' días' : '—' }}</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full table-agro">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Recibido</th>
                    <th>Entregado</th>
                    <th>Técnico</th>
                    <th class="text-right">Total</th>
                    <th>Pago</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><a href="{{ route('reparaciones.show', $order->id) }}" class="font-semibold text-indigo-600">{{ $order->order_number }}</a></td>
                        <td>{{ $order->client_name }}</td>
                        <td>{{ $order->device_brand }} {{ $order->device_model }}</td>
                        <td>{{ $order->received_date?->format('d/m/Y') }}</td>
                        <td>{{ $order->delivered_date?->format('d/m/Y') }}</td>
                        <td>{{ $order->technician?->name ?? 'Sin asignar' }}</td>
                        <td class="text-right font-semibold">@money($order->total, 2)</td>
                        <td>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $order->paymentStateColor() }}">
                                {{ $order->paymentStateLabel() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-12 text-slate-400">No hay reparaciones entregadas en esta semana.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
