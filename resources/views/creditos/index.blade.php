@extends('layouts.app')

@section('title', 'Créditos')

@section('content')

<div class="space-y-6">

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h2 class="page-title">Gestión de Créditos</h2>
            <p class="page-subtitle">Cartera, límites y abonos de clientes</p>
        </div>
        <a href="{{ route('creditos.report') }}" class="btn-primary text-sm">Reporte Completo</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">Cartera Pendiente</p>
            <p class="text-xl font-bold text-indigo-600">C$ {{ number_format($portfolio['balance_total'], 2) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-red-500">
            <p class="text-xs text-slate-500">Vencida</p>
            <p class="text-xl font-bold text-red-600">C$ {{ number_format($portfolio['overdue_total'], 2) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-violet-500">
            <p class="text-xs text-slate-500">Clientes con Crédito</p>
            <p class="text-xl font-bold text-violet-600">{{ $portfolio['clients_with_credit'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Sobre Límite</p>
            <p class="text-xl font-bold text-amber-600">{{ $portfolio['over_limit_count'] }}</p>
        </div>
    </div>

    <div class="flex gap-1 border-b border-slate-200">
        <a href="{{ route('creditos.index') }}" class="tab-link tab-link-active">Clientes con Deuda</a>
        <a href="{{ route('creditos.overdue') }}" class="tab-link tab-link-inactive">Vencidos</a>
        <a href="{{ route('creditos.report') }}" class="tab-link tab-link-inactive">Reporte Pro</a>
    </div>

    <form method="get" class="card p-4">
        <div class="flex gap-2">
            <input type="text" name="search" placeholder="Buscar cliente..." value="{{ request('search') }}" class="input-field flex-1">
            <button type="submit" class="btn-primary">Buscar</button>
        </div>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full table-agro">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="text-right">Límite</th>
                    <th class="text-right">Deuda</th>
                    <th class="text-right">Abonos</th>
                    <th class="text-right">Saldo</th>
                    <th class="text-center">Plazo</th>
                    <th class="text-center">Uso</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientsWithDebt as $item)
                <tr>
                    <td>
                        <p class="font-semibold text-slate-900">{{ $item['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $item['phone'] ?? '' }}</p>
                    </td>
                    <td class="text-right text-sm">
                        {{ (float)($item['credit_limit'] ?? 0) > 0 ? 'C$ '.number_format($item['credit_limit'], 2) : 'Ilimitado' }}
                    </td>
                    <td class="text-right">C$ {{ number_format($item['total_debt'] ?? 0, 2) }}</td>
                    <td class="text-right text-emerald-700">C$ {{ number_format($item['total_paid'] ?? 0, 2) }}</td>
                    <td class="text-right font-bold {{ ($item['balance'] ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        C$ {{ number_format($item['balance'] ?? 0, 2) }}
                    </td>
                    <td class="text-center text-sm">{{ $item['credit_days'] ?? 30 }}d</td>
                    <td class="text-center">
                        @if(($item['over_limit'] ?? false))
                        <span class="badge-danger">Excedido</span>
                        @elseif(($item['usage_percent'] ?? 0) > 80)
                        <span class="badge-warning">{{ $item['usage_percent'] }}%</span>
                        @else
                        <span class="text-slate-500 text-sm">{{ $item['usage_percent'] ?? 0 }}%</span>
                        @endif
                    </td>
                    <td class="text-center space-x-2">
                        <a href="{{ route('creditos.show', $item['id']) }}" class="text-indigo-600 text-sm font-medium">Ver</a>
                        @if(($item['balance'] ?? 0) > 0)
                        <a href="{{ route('creditos.create', $item['id']) }}" class="text-emerald-600 text-sm font-medium">Abono</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-8 text-slate-500">No hay deudas pendientes</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
