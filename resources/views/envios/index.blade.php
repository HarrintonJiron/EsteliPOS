@extends('layouts.app')
@section('title','Envíos')
@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><h1 class="page-title">Envíos</h1><p class="page-subtitle">Seguimiento de entregas nacionales</p></div><a class="btn-primary text-center" href="{{ route('envios.create') }}">Nuevo envío</a></div>
@include('operaciones-clientes._tabs')
<form class="card mb-5 flex flex-col gap-3 p-4 sm:flex-row"><select name="department" class="select-field min-w-0 flex-1"><option value="">Todos los departamentos</option>@foreach($departments as $d)<option @selected(request('department')===$d)>{{ $d }}</option>@endforeach</select><select name="status" class="select-field min-w-0 flex-1"><option value="">Todos los estados</option>@foreach(['pending'=>'Pendiente','prepared'=>'Preparado','shipped'=>'Enviado','delivered'=>'Entregado','cancelled'=>'Cancelado'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form>
@php($canEditShipments = auth()->user()?->isAdmin() || auth()->user()?->hasPermission('envios.edit'))
<div class="card overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr><th>Número</th><th>Destinatario</th><th>Destino</th><th>Factura</th><th>Transportista</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            @forelse($shipments as $s)
                <tr>
                    <td><a class="text-indigo-600 font-semibold" href="{{ route('envios.show',$s) }}">{{ $s->number }}</a></td>
                    <td>{{ $s->recipient_name }}<br><span class="text-slate-500">{{ $s->recipient_phone }}</span></td>
                    <td>{{ $s->municipality ? $s->municipality.', ' : '' }}{{ $s->department }}</td>
                    <td>{{ $s->sale?->invoice_number ?? '—' }}</td>
                    <td>{{ $s->carrier ?: '—' }}</td>
                    <td>
                        @if($canEditShipments)
                            <form method="POST" action="{{ route('envios.status', $s) }}" class="inline">
                                @csrf @method('PATCH')
                                <select name="status" aria-label="Estado del envío {{ $s->number }}" onchange="this.form.submit()"
                                        class="cursor-pointer rounded-full border-0 py-1 pl-3 pr-8 text-xs font-semibold {{ $s->statusColor() }}">
                                    @foreach(\App\Models\Shipment::STATUS_LABELS as $value => $label)
                                        <option value="{{ $value }}" @selected($s->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $s->statusColor() }}">{{ $s->statusLabel() }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <a class="font-semibold text-indigo-600" href="{{ route('envios.show',$s) }}">Ver</a>
                            @if($canEditShipments)
                                <a class="font-semibold text-emerald-700" href="{{ route('envios.edit',$s) }}">Editar</a>
                            @endif
                            <a class="text-slate-600 hover:text-slate-900" href="{{ route('envios.label',$s) }}" target="print-window">Imprimir etiqueta</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-500">No hay envíos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div><div class="mt-4">{{ $shipments->links() }}</div>
@endsection
