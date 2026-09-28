@extends('layouts.app')

@section('title', 'Créditos')

@section('content')

<div class="ex-shell">

    <x-ui.command-hero
        kicker="Cartera"
        title="Créditos"
        subtitle="Cartera, límites y abonos de clientes"
        metric-label="Cartera pendiente"
        :metric-value="app(\App\Services\MoneyDisplayService::class)->format($portfolio['balance_total'], 0)"
        :meta="[$portfolio['clients_with_credit'] . ' clientes', 'Vencida ' . $currencySymbol . ' ' . number_format($portfolio['overdue_total'], 0)]"
        :stats="[
            ['label' => 'Vencida', 'value' => app(\App\Services\MoneyDisplayService::class)->format($portfolio['overdue_total'], 0)],
            ['label' => 'Con crédito', 'value' => number_format($portfolio['clients_with_credit'])],
            ['label' => 'Sobre límite', 'value' => number_format($portfolio['over_limit_count'])],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('creditos.report') }}" class="ex-btn ex-btn--solid">Reporte completo</a>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="ex-kpis ex-kpis--4">
        <x-ui.command-kpi label="Cartera pendiente" :value="app(\App\Services\MoneyDisplayService::class)->format($portfolio['balance_total'], 0)" />
        <x-ui.command-kpi label="Vencida" :value="app(\App\Services\MoneyDisplayService::class)->format($portfolio['overdue_total'], 0)" />
        <x-ui.command-kpi label="Con crédito" :value="number_format($portfolio['clients_with_credit'])" />
        <x-ui.command-kpi label="Sobre límite" :value="number_format($portfolio['over_limit_count'])" />
    </div>

    <div class="flex gap-1 overflow-x-auto border-b border-slate-200">
        <a href="{{ route('creditos.index') }}" class="tab-link tab-link-active">Clientes con Deuda</a>
        <a href="{{ route('creditos.overdue') }}" class="tab-link tab-link-inactive">Vencidos</a>
        <a href="{{ route('creditos.report') }}" class="tab-link tab-link-inactive">Reporte Pro</a>
    </div>

    <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Tipo de crédito">
        @foreach(['all' => 'Todos los créditos', 'sales' => 'Facturas a crédito', 'repairs' => 'Créditos de reparaciones'] as $key => $label)
            <a href="{{ route('creditos.index', array_filter(['type' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
               class="rounded-full border px-4 py-1.5 text-sm font-semibold transition {{ $type === $key ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300' }}">
                {{ $label }} <span class="ml-1 rounded-full px-2 py-0.5 text-xs {{ $type === $key ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts[$key] }}</span>
            </a>
        @endforeach
        @if(($portfolio['repairs_balance'] ?? 0) > 0)
            <span class="ml-auto text-sm text-slate-500">Reparaciones a crédito: <strong class="text-violet-700">@money($portfolio['repairs_balance'], 2)</strong>
                @if(($portfolio['repairs_overdue'] ?? 0) > 0)· vencido <strong class="text-red-600">@money($portfolio['repairs_overdue'], 2)</strong>@endif</span>
        @endif
    </div>

    <form method="get" class="card p-4">
        @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
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
                        <p class="mt-1 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                            @if(($item['sales_balance'] ?? 0) > 0.00001)<span class="rounded-full bg-sky-100 px-2 py-0.5 text-sky-800">Facturas @money($item['sales_balance'], 2)</span>@endif
                            @if(($item['repairs_balance'] ?? 0) > 0.00001)<span class="rounded-full bg-violet-100 px-2 py-0.5 text-violet-800">Reparaciones @money($item['repairs_balance'], 2) · {{ $item['repairs_count'] }}</span>@endif
                        </p>
                    </td>
                    <td class="text-right text-sm">
                        {{ (float)($item['credit_limit'] ?? 0) > 0 ? app(\App\Services\MoneyDisplayService::class)->format($item['credit_limit'], 2) : 'Ilimitado' }}
                    </td>
                    <td class="text-right">@money($item['total_debt'] ?? 0, 2)</td>
                    <td class="text-right text-emerald-700">@money($item['total_paid'] ?? 0, 2)</td>
                    <td class="text-right font-bold {{ ($item['balance'] ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        @money($item['balance'] ?? 0, 2)
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

    @if($type === 'repairs')
    <div class="card overflow-hidden">
        <div class="border-b border-violet-100 bg-violet-50 px-5 py-3">
            <h2 class="font-bold text-violet-900">Reparaciones a crédito pendientes</h2>
            <p class="text-xs text-violet-700">Cada orden con su vencimiento y saldo. Desde aquí se registra el abono.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-agro">
                <thead>
                    <tr>
                        <th>Orden</th><th>Cliente</th><th>Equipo</th><th class="text-center">Vence</th>
                        <th class="text-right">Total</th><th class="text-right">Saldo</th><th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repairCredits as $credit)
                        @php $order = $credit['order']; $isOverdue = $order->due_date?->copy()->startOfDay()->isBefore(now()->startOfDay()); @endphp
                        <tr>
                            <td><a class="font-semibold text-indigo-600" href="{{ route('reparaciones.show', $order->id) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $credit['client']->legal_name ?? $credit['client']->name }}</td>
                            <td>{{ $order->device_brand }} {{ $order->device_model }}</td>
                            <td class="text-center">
                                @if($order->due_date)
                                    <span class="inline-block rounded px-2 py-0.5 text-xs font-semibold {{ $isOverdue ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $order->due_date->format($companyProfile['date_format']) }}</span>
                                @else — @endif
                            </td>
                            <td class="text-right">@money($order->total, 2)</td>
                            <td class="text-right font-bold text-red-600">@money($credit['balance'], 2)</td>
                            <td class="text-center space-x-2">
                                <a href="{{ route('creditos.show', $credit['client']->id) }}" class="text-sm font-medium text-indigo-600">Cliente</a>
                                <a href="{{ route('creditos.create', ['clientId' => $credit['client']->id, 'apply_to' => 'repair:'.$order->id]) }}" class="text-sm font-medium text-emerald-600">Abonar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">No hay reparaciones a crédito pendientes</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
