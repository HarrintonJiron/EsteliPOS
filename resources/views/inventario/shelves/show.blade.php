@extends('layouts.app')
@section('title', 'Estante '.$shelf->code)
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')
    <div class="flex flex-wrap items-start justify-between gap-3"><div><h1 class="page-title">{{ $shelf->code }} · {{ $shelf->name ?: 'Estante' }}</h1><p class="page-subtitle">{{ $shelf->warehouse?->name }} · Productos asignados a esta ubicación</p></div><div class="flex gap-2"><a href="{{ route('inventario.shelves.index') }}" class="btn-outline">Todos</a><a href="{{ route('inventario.shelves.edit', $shelf) }}" class="btn-primary">Editar</a></div></div>
    @if(session('success'))<div class="card bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    <div class="grid grid-cols-2 gap-3"><div class="card p-4"><p class="text-xs uppercase text-slate-500">Productos registrados</p><p class="text-2xl font-bold">{{ $stocks->total() }}</p></div><div class="card p-4"><p class="text-xs uppercase text-slate-500">Existencias totales</p><p class="text-2xl font-bold text-indigo-700">{{ rtrim(rtrim(number_format($totalQuantity, 4, '.', ''), '0'), '.') }}</p></div></div>
    <div class="card overflow-hidden"><div class="overflow-x-auto"><table class="table-agro w-full text-sm"><thead><tr><th>Producto</th><th>Código</th><th>Categoría</th><th class="text-right">Cantidad</th><th></th></tr></thead><tbody>@forelse($stocks as $stock)<tr><td class="font-medium">{{ $stock->product?->name }}</td><td class="font-mono text-xs">{{ $stock->product?->code }}</td><td>{{ $stock->product?->category?->name ?? '—' }}</td><td class="text-right font-semibold">{{ rtrim(rtrim(number_format($stock->quantity, 4, '.', ''), '0'), '.') }}</td><td><a href="{{ route('inventario.show', $stock->product_id) }}" class="text-indigo-600">Ver producto</a></td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-500">No hay productos asignados a este estante.</td></tr>@endforelse</tbody></table></div>@if($stocks->hasPages())<div class="border-t p-3">{{ $stocks->links() }}</div>@endif</div>
</div>
@endsection
