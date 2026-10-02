@extends('layouts.app')
@section('title', 'Apartados')
@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><h1 class="page-title">Apartados</h1><p class="page-subtitle">Artículos reservados para clientes</p></div><a href="{{ route('apartados.create') }}" class="btn-primary text-center">Nuevo apartado</a></div>
@include('operaciones-clientes._tabs')
<form class="card mb-5 flex flex-col gap-3 p-4 sm:flex-row"><input class="input-field min-w-0 flex-1" name="search" value="{{ request('search') }}" placeholder="Número o cliente"><select class="select-field sm:w-52" name="status"><option value="">Todos</option>@foreach(['active'=>'Activo','completed'=>'Completado','expired'=>'Vencido','cancelled'=>'Cancelado'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form>
<div class="card overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Número</th><th>Cliente</th><th>Artículos</th><th>Vence</th><th>Total</th><th>Anticipo</th><th>Estado</th></tr></thead><tbody>@forelse($reservations as $r)<tr><td><a class="text-indigo-600 font-semibold" href="{{ route('apartados.show',$r) }}">{{ $r->number }}</a></td><td>{{ $r->client->name }}</td><td>{{ $r->items->sum('quantity') }}</td><td>{{ $r->expires_at?->format('d/m/Y H:i') ?? 'Sin vencimiento' }}</td><td>C$ {{ number_format($r->total,2) }}</td><td>C$ {{ number_format($r->deposit,2) }}</td><td>{{ ucfirst($r->status) }}</td></tr>@empty<tr><td colspan="7" class="p-8 text-center text-slate-500">No hay apartados.</td></tr>@endforelse</tbody></table></div>
<div class="mt-4">{{ $reservations->links() }}</div>
@endsection
