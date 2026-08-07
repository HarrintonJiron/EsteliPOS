@extends('layouts.app')
@section('title', $warehouse->name)
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-start gap-3">
        <div>
            <h1 class="page-title">{{ $warehouse->name }}</h1>
            <p class="page-subtitle">{{ $warehouse->code }} · Valor estimado C$ {{ number_format($totalValue, 2) }}</p>
        </div>
        <a href="{{ route('inventario.warehouses.edit', $warehouse) }}" class="btn-outline text-sm">Editar</a>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full table-agro text-sm">
            <thead><tr><th>Producto</th><th>Ubicación</th><th class="text-right">Stock</th><th>Unidad</th></tr></thead>
            <tbody>
                @forelse($stocks as $stock)
                <tr>
                    <td><a href="{{ route('inventario.show', $stock->product_id) }}" class="text-link font-medium">{{ $stock->product->name }}</a></td>
                    <td>{{ $stock->aisle ?: '—' }}</td>
                    <td class="text-right font-semibold">{{ rtrim(rtrim(number_format($stock->quantity, 4, '.', ''), '0'), '.') }}</td>
                    <td>{{ $stock->product->baseUnitLabel() }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-8 text-slate-400">Sin existencias en esta bodega</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($stocks->hasPages())<div class="p-3 border-t">{{ $stocks->links() }}</div>@endif
    </div>
</div>
@endsection
