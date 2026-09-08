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
                @if($list->is_default)<span class="badge-success text-xs">Default</span>@endif
            </div>
            <p class="text-xs text-slate-500 mt-1">{{ $list->code }} · {{ $list->items_count }} precios</p>
        </a>
        @endforeach
    </div>
</div>
@endsection
