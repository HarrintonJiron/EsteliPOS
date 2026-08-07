@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Cliente · ' . $client->legal_name)

@section('content')
@php
    $balance = (float) ($creditSummary['balance'] ?? 0);
    $availableCredit = $creditSummary['available_credit'] ?? null;
    $isOverLimit = (bool) ($creditSummary['over_limit'] ?? false);
    $isActive = ($client->status ?? 'active') === 'active';
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('clientes.index') }}" class="btn-outline text-sm">← Volver</a>
                <span class="badge-info">{{ $client->isCompany() ? 'Empresa' : 'Persona Natural' }}</span>
                <span class="{{ $isActive ? 'badge-success' : 'badge-warning' }}">{{ $isActive ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <h1 class="page-title mt-3">{{ $client->legal_name }}</h1>
            <p class="page-subtitle">Ficha del cliente con información fiscal, contacto y crédito.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('clientes.edit', $client->id) }}" class="btn-outline text-sm">Editar cliente</a>
            @if($client->credit_enabled)
                <a href="{{ route('creditos.show', $client->id) }}" class="btn-secondary text-sm">Ver créditos</a>
                <a href="{{ route('creditos.statement', $client->id) }}" target="_blank" class="btn-primary text-sm">Imprimir estado de cuenta</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">{{ $client->document_label }}</p>
            <p class="text-lg font-bold text-slate-800">{{ $client->document_number ?? '—' }}</p>
        </div>
        <div class="card p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-slate-500">Saldo pendiente</p>
            <p class="text-lg font-bold {{ $balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">C$ {{ number_format($balance, 2) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-violet-500">
            <p class="text-xs text-slate-500">Límite de crédito</p>
            <p class="text-lg font-bold text-violet-700">
                @if($client->credit_enabled)
                    {{ (float) $client->credit_limit > 0 ? 'C$ '.number_format($client->credit_limit, 2) : 'Ilimitado' }}
                @else
                    Contado
                @endif
            </p>
        </div>
        <div class="card p-4 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Disponible</p>
            <p class="text-lg font-bold text-amber-700">
                @if($client->credit_enabled)
                    @if($availableCredit === null)
                        Ilimitado
                    @else
                        C$ {{ number_format((float) $availableCredit, 2) }}
                    @endif
                @else
                    —
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="card p-6 xl:col-span-2">
            <h2 class="font-semibold text-slate-900">Perfil del cliente</h2>
            <p class="text-sm text-slate-500 mt-1">Datos generales y fiscales para facturación.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                <div>
                    <p class="text-xs text-slate-500">Nombre / Razón social</p>
                    <p class="font-semibold text-slate-800">{{ $client->legal_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Tipo de contribuyente</p>
                    <p class="font-semibold text-slate-800">{{ $client->taxpayer_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Teléfono</p>
                    <p class="font-semibold text-slate-800">{{ $client->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Correo</p>
                    <p class="font-semibold text-slate-800">{{ $client->email ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Departamento / Municipio</p>
                    <p class="font-semibold text-slate-800">{{ $client->formatted_location ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Código interno</p>
                    <p class="font-semibold text-slate-800">{{ $client->code ?? '—' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500">Dirección</p>
                    <p class="font-semibold text-slate-800">{{ $client->address ?? '—' }}</p>
                </div>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="font-semibold text-slate-900">Estado de crédito</h2>
            <p class="text-sm text-slate-500 mt-1">Resumen financiero del cliente.</p>

            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Crédito habilitado</span>
                    <strong class="text-slate-800">{{ $client->credit_enabled ? 'Sí' : 'No' }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Plazo</span>
                    <strong class="text-slate-800">{{ $creditSummary['credit_days'] ?? 30 }} días</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Mora acumulada</span>
                    <strong class="text-slate-800">C$ {{ number_format((float) ($creditSummary['mora'] ?? 0), 2) }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Riesgo</span>
                    @if($client->credit_enabled && $isOverLimit)
                        <span class="badge-danger">Sobre límite</span>
                    @elseif($client->credit_enabled && $balance > 0)
                        <span class="badge-warning">Con deuda</span>
                    @elseif($client->credit_enabled)
                        <span class="badge-success">Al día</span>
                    @else
                        <span class="badge-info">Sin crédito</span>
                    @endif
                </div>
            </div>

            @if($client->credit_enabled)
                <div class="mt-5 p-3 rounded-xl border border-slate-200 bg-slate-50 text-sm">
                    <p class="text-slate-500">Saldo pendiente</p>
                    <p class="text-xl font-bold {{ $balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">C$ {{ number_format($balance, 2) }}</p>
                </div>
            @endif
        </section>
    </div>

    <section class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-slate-900">Historial reciente de facturas</h2>
                <p class="text-sm text-slate-500">Últimas 10 facturas asociadas al cliente.</p>
            </div>
            <span class="text-xs text-slate-500">{{ $client->sales->count() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full table-agro">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th class="text-right">Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($client->sales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('facturacion.show', $sale->id) }}" class="text-indigo-600 hover:underline font-medium">
                                    {{ $sale->invoice_number ?: ('#' . $sale->id) }}
                                </a>
                            </td>
                            <td>{{ $sale->date?->format('d/m/Y') }}</td>
                            <td>{{ ucfirst($sale->payment_type) }}</td>
                            <td class="text-right font-semibold">C$ {{ number_format((float) $sale->total, 2) }}</td>
                            <td>
                                @if($sale->status === 'completed')
                                    <span class="badge-success">Pagada</span>
                                @elseif($sale->status === 'pending')
                                    <span class="badge-warning">Pendiente</span>
                                @else
                                    <span class="badge-info">{{ ucfirst($sale->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-slate-500">Sin facturas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
