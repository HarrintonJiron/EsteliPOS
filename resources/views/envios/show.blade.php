@extends('layouts.app')
@section('title',$shipment->number)
@section('content')
@include('operaciones-clientes._tabs')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><h1 class="page-title">{{ $shipment->number }}</h1><p class="page-subtitle truncate">{{ $shipment->recipient_name }} · {{ ucfirst($shipment->status) }}</p></div><a class="btn-secondary text-center" href="{{ route('envios.edit',$shipment) }}">Editar</a></div>
<div class="card p-6 grid md:grid-cols-2 gap-5"><div><span class="text-slate-500">Destino</span><p class="font-semibold">{{ $shipment->municipality ? $shipment->municipality.', ' : '' }}{{ $shipment->department }}</p><p>{{ $shipment->address }}</p><p>{{ $shipment->reference }}</p></div><div><span class="text-slate-500">Transporte</span><p>{{ $shipment->carrier ?: 'Sin asignar' }}</p><p>Guía: {{ $shipment->tracking_number ?: '—' }}</p><p>Costo: C$ {{ number_format($shipment->shipping_cost,2) }}</p></div>@if($shipment->sale)<div><span class="text-slate-500">Factura</span><p><a class="text-indigo-600" href="{{ route('facturacion.show',$shipment->sale) }}">{{ $shipment->sale->invoice_number }}</a></p></div>@endif</div>
@endsection
