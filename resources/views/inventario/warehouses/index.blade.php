@extends('layouts.app')
@section('title', 'Bodegas')
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="page-title">Bodegas</h1>
            <p class="page-subtitle">Separación de existencias por ubicación física</p>
        </div>
        <a href="{{ route('inventario.warehouses.create') }}" class="btn-primary text-sm">+ Nueva bodega</a>
    </div>

    @if(session('success'))
        <div class="card p-3 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid md:grid-cols-3 gap-4">
        @foreach($warehouses as $wh)
        <a href="{{ route('inventario.warehouses.show', $wh) }}" class="card p-4 hover:ring-2 hover:ring-indigo-200 transition block">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-mono text-xs text-slate-500">{{ $wh->code }}</p>
                    <h3 class="font-bold text-slate-900">{{ $wh->name }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $wh->city ?: 'Nicaragua' }}</p>
                </div>
                @if($wh->is_default)<span class="badge-success text-[10px]">Principal</span>@endif
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ $wh->products_count }} productos con stock</p>
        </a>
        @endforeach
    </div>
</div>
@endsection
