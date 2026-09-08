@extends('layouts.app')
@section('title', 'Estantes')
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h1 class="page-title">Estantes</h1><p class="page-subtitle">Ubicaciones internas de productos dentro de cada bodega</p></div>
        <a href="{{ route('inventario.shelves.create') }}" class="btn-primary text-sm">+ Nuevo estante</a>
    </div>
    @if(session('success'))<div class="card bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach([['Total', $stats['total']], ['Activos', $stats['active']], ['Con registros', $stats['with_products']]] as [$label, $value])
            <div class="card p-4"><p class="text-xs uppercase text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold text-slate-800">{{ $value }}</p></div>
        @endforeach
    </div>
    <form method="GET" class="card grid grid-cols-1 gap-3 p-4 sm:grid-cols-[1fr_1fr_auto]">
        <input name="q" value="{{ $search }}" class="input-field" placeholder="Buscar código, nombre o bodega">
        <select name="warehouse_id" class="select-field"><option value="">Todas las bodegas</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($warehouseId == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select>
        <button class="btn-primary">Filtrar</button>
    </form>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($shelves as $shelf)
            <div class="card flex flex-col gap-3 p-4 {{ $shelf->is_active ? '' : 'opacity-60' }}">
                <div><p class="font-mono text-xs text-indigo-600">{{ $shelf->code }}</p><h2 class="font-bold text-slate-900">{{ $shelf->name ?: 'Sin descripción' }}</h2><p class="text-xs text-slate-500">{{ $shelf->warehouse?->name }}</p></div>
                <div class="grid grid-cols-2 gap-2 text-sm"><div class="rounded-lg bg-slate-50 p-2"><p class="text-xs text-slate-500">Productos</p><strong>{{ $shelf->products_count }}</strong></div><div class="rounded-lg bg-slate-50 p-2"><p class="text-xs text-slate-500">Existencias</p><strong>{{ rtrim(rtrim(number_format($shelf->total_quantity, 4, '.', ''), '0'), '.') }}</strong></div></div>
                <div class="mt-auto flex gap-2"><a href="{{ route('inventario.shelves.show', $shelf) }}" class="btn-primary btn-sm">Ver registros</a><a href="{{ route('inventario.shelves.edit', $shelf) }}" class="btn-outline btn-sm">Editar</a></div>
            </div>
        @empty
            <div class="card p-8 text-center text-slate-500 md:col-span-2 xl:col-span-3">No hay estantes registrados.</div>
        @endforelse
    </div>
    @if($shelves->hasPages())<div>{{ $shelves->links() }}</div>@endif
</div>
@endsection
