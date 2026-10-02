@extends('layouts.app')
@section('title', 'Apartados y envíos')
@section('content')
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div><p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Operación al cliente</p><h1 class="page-title">Apartados y envíos</h1><p class="page-subtitle">Reserva productos y acompaña cada entrega desde un mismo lugar.</p></div>
    <div class="flex flex-wrap gap-2">@if($canReservations && (auth()->user()->isAdmin() || auth()->user()->hasPermission('apartados.create')))<a href="{{ route('apartados.create') }}" class="btn-primary">＋ Nuevo apartado</a>@endif @if($canShipments && (auth()->user()->isAdmin() || auth()->user()->hasPermission('envios.create')))<a href="{{ route('envios.create') }}" class="btn-secondary">＋ Nuevo envío</a>@endif</div>
</div>
@include('operaciones-clientes._tabs')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['Apartados activos',$activeReservations,'Reservas que bloquean inventario','bg-indigo-50 text-indigo-700'],
        ['Valor reservado','C$ '.number_format($reservedValue,2),'Antes de restar anticipos','bg-violet-50 text-violet-700'],
        ['Envíos en proceso',$pendingShipments,'Pendientes, preparados o enviados','bg-sky-50 text-sky-700'],
        ['Entregas completadas',$deliveredShipments,'Histórico entregado','bg-emerald-50 text-emerald-700'],
    ] as [$label,$value,$hint,$color])
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $color }}">{{ $label }}</span><p class="mt-4 text-3xl font-bold text-slate-900">{{ $value }}</p><p class="mt-1 text-sm text-slate-500">{{ $hint }}</p></article>
    @endforeach
</div>
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="card overflow-hidden"><div class="flex items-center justify-between border-b border-slate-100 p-5"><div><h2 class="font-bold text-slate-900">Apartados recientes</h2><p class="text-sm text-slate-500">Reservas creadas recientemente</p></div><a class="text-sm font-semibold text-indigo-600" href="{{ route('apartados.index') }}">Ver todos →</a></div><div class="divide-y divide-slate-100">@forelse($reservations as $reservation)<a href="{{ route('apartados.show',$reservation) }}" class="flex items-center justify-between gap-3 p-4 hover:bg-slate-50"><div><p class="font-semibold text-slate-800">{{ $reservation->client->name }}</p><p class="text-xs text-slate-500">{{ $reservation->number }}</p></div><div class="text-right"><p class="font-semibold">C$ {{ number_format($reservation->total,2) }}</p><span class="text-xs text-indigo-600">{{ ucfirst($reservation->status) }}</span></div></a>@empty<p class="p-8 text-center text-sm text-slate-500">Aún no hay apartados.</p>@endforelse</div></section>
    <section class="card overflow-hidden"><div class="flex items-center justify-between border-b border-slate-100 p-5"><div><h2 class="font-bold text-slate-900">Envíos recientes</h2><p class="text-sm text-slate-500">Últimos destinos registrados</p></div><a class="text-sm font-semibold text-sky-600" href="{{ route('envios.index') }}">Ver todos →</a></div><div class="divide-y divide-slate-100">@forelse($shipments as $shipment)<a href="{{ route('envios.show',$shipment) }}" class="flex items-center justify-between gap-3 p-4 hover:bg-slate-50"><div><p class="font-semibold text-slate-800">{{ $shipment->recipient_name }}</p><p class="text-xs text-slate-500">{{ $shipment->department }} · {{ $shipment->number }}</p></div><span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ ucfirst($shipment->status) }}</span></a>@empty<p class="p-8 text-center text-sm text-slate-500">Aún no hay envíos.</p>@endforelse</div></section>
</div>
@endsection
