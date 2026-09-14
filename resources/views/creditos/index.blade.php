@extends('layouts.app')

@section('title', 'Créditos')

@section('content')

<div class="ex-shell">

    <x-ui.command-hero
        kicker="Cartera"
        title="Créditos"
        subtitle="Cartera, límites y abonos de clientes"
        metric-label="Cartera pendiente"
        :metric-value="'C$ ' . number_format($portfolio['balance_total'], 0)"
        :meta="[$portfolio['clients_with_credit'] . ' clientes', 'Vencida C$ ' . number_format($portfolio['overdue_total'], 0)]"
        :stats="[
            ['label' => 'Vencida', 'value' => 'C$ ' . number_format($portfolio['overdue_total'], 0)],
            ['label' => 'Con crédito', 'value' => number_format($portfolio['clients_with_credit'])],
            ['label' => 'Sobre límite', 'value' => number_format($portfolio['over_limit_count'])],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('creditos.report') }}" class="ex-btn ex-btn--solid">Reporte completo</a>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="ex-kpis ex-kpis--4">
        <x-ui.command-kpi label="Cartera pendiente" :value="'C$ ' . number_format($portfolio['balance_total'], 0)" />
        <x-ui.command-kpi label="Vencida" :value="'C$ ' . number_format($portfolio['overdue_total'], 0)" />
        <x-ui.command-kpi label="Con crédito" :value="number_format($portfolio['clients_with_credit'])" />
        <x-ui.command-kpi label="Sobre límite" :value="number_format($portfolio['over_limit_count'])" />
    </div>

    <div class="flex gap-1 overflow-x-auto border-b border-slate-200">
        <a href="{{ route('creditos.index') }}" class="tab-link tab-link-active">Clientes con Deuda</a>
        <a href="{{ route('creditos.overdue') }}" class="tab-link tab-link-inactive">Vencidos</a>
        <a href="{{ route('creditos.report') }}" class="tab-link tab-link-inactive">Reporte Pro</a>
    </div>

    <form method="get" class="card p-4">
        <div class="flex gap-2">
            <input type="text" name="search" placeholder="Buscar cliente, cédula o RUC..." value="{{ request('search') }}" class="input-field flex-1">
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
                        <p class="font-semibold text-slate-900">{{ $item['legal_name'] ?? $item['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ ($item['client_type'] ?? 'natural') === 'company' ? 'Empresa' : 'Persona Natural' }} · {{ $item['document_label'] ?? 'Documento' }}: {{ $item['document_number'] ?? '—' }}</p>
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
