@extends('layouts.app')
@section('title', 'Listas de Precios')
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="page-title">Listas de precios</h1>
            <p class="page-subtitle">Precios por canal: público, mayorista, constructor</p>
        </div>
        <a href="{{ route('inventario.price-lists.create') }}" class="btn-primary text-sm">+ Nueva lista</a>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        @foreach($priceLists as $list)
        <a href="{{ route('inventario.price-lists.show', $list) }}" class="card p-4 block hover:ring-2 hover:ring-indigo-200">
            <div class="flex justify-between">
                <h3 class="font-bold">{{ $list->name }}</h3>
                <span class="{{ $list->isCurrentlyValid() ? 'badge-success' : 'badge-danger' }} text-xs">{{ $list->isCurrentlyValid() ? 'Vigente' : 'No vigente' }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">{{ $list->code }} · {{ $list->items_count }} escalas @if($list->is_default) · Predeterminada @endif</p>
            @if($list->valid_from || $list->valid_to)<p class="text-xs text-slate-400 mt-2">Vigencia: {{ $list->valid_from?->format('d/m/Y') ?? 'siempre' }} — {{ $list->valid_to?->format('d/m/Y') ?? 'sin vencimiento' }}</p>@endif
        </a>
        @endforeach
    </div>
</div>
@endsection
