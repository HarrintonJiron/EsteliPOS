@extends('layouts.app')
@section('title',$shipment->number)
@section('content')
@include('operaciones-clientes._tabs')
@php($canEditShipments = auth()->user()?->isAdmin() || auth()->user()?->hasPermission('envios.edit'))
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="page-title">{{ $shipment->number }}</h1>
            <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $shipment->statusColor() }}">{{ $shipment->statusLabel() }}</span>
        </div>
        <p class="page-subtitle truncate">{{ $shipment->recipient_name }}@if($shipment->recipient_phone) · {{ $shipment->recipient_phone }}@endif</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a class="btn-secondary text-center" href="{{ route('envios.label',$shipment) }}" target="print-window">Imprimir etiqueta</a>
        @if($canEditShipments)
            <a class="btn-primary text-center" href="{{ route('envios.edit',$shipment) }}">Editar envío</a>
        @endif
    </div>
</div>

@if($canEditShipments)
<form method="POST" action="{{ route('envios.status', $shipment) }}" class="card mb-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
    @csrf @method('PATCH')
    <label for="shipment-status" class="text-sm font-semibold text-slate-700">Estado del envío</label>
    <select id="shipment-status" name="status" class="select-field sm:max-w-xs">
        @foreach(\App\Models\Shipment::STATUS_LABELS as $value => $label)
            <option value="{{ $value }}" @selected($shipment->status === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn-secondary">Actualizar estado</button>
    <span class="text-xs text-slate-500 sm:ml-auto">
        @if($shipment->shipped_at)Enviado {{ $shipment->shipped_at->format($companyProfile['date_format'].' H:i') }}@endif
        @if($shipment->delivered_at) · Entregado {{ $shipment->delivered_at->format($companyProfile['date_format'].' H:i') }}@endif
    </span>
</form>
@endif

<div class="card grid gap-5 p-6 md:grid-cols-2">
    <div>
        <span class="text-slate-500">Destino</span>
        <p class="font-semibold">{{ $shipment->municipality ? $shipment->municipality.', ' : '' }}{{ $shipment->department }}</p>
        <p>{{ $shipment->address }}</p>
        <p>{{ $shipment->reference }}</p>
        @if($shipment->is_fragile)<p class="mt-1 inline-block rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Frágil</p>@endif
    </div>
    <div>
        <span class="text-slate-500">Transporte</span>
        <p>{{ $shipment->carrier ?: 'Sin asignar' }}</p>
        <p>Guía: {{ $shipment->tracking_number ?: '—' }}</p>
        <p>Costo: @money($shipment->shipping_cost,2)</p>
    </div>
    @if($shipment->sale)
    <div>
        <span class="text-slate-500">Factura</span>
        <p><a class="text-indigo-600" href="{{ route('facturacion.show',$shipment->sale) }}">{{ $shipment->sale->invoice_number }}</a></p>
    </div>
    @endif
    @if($shipment->notes)
    <div>
        <span class="text-slate-500">Notas internas</span>
        <p class="whitespace-pre-line">{{ $shipment->notes }}</p>
    </div>
    @endif
</div>
@endsection