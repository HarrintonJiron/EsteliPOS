@extends('layouts.app')
@section('title', $priceList->name)
@section('content')
<div class="space-y-5">
    <div class="flex justify-between gap-3 flex-wrap">
        <div><h1 class="page-title">{{ $priceList->name }}</h1><p class="page-subtitle">{{ $priceList->code }}</p></div>
        <a href="{{ route('inventario.price-lists.edit', $priceList) }}" class="btn-outline text-sm">Editar lista</a>
    </div>
    @if(session('success'))<div class="card p-3 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('inventario.price-lists.items.store', $priceList) }}" class="card p-4 grid md:grid-cols-5 gap-3 items-end">
        @csrf
        <div class="md:col-span-2"><label class="form-label">Producto</label><select name="product_id" class="select-field" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>@endforeach</select></div>
        <div><label class="form-label">Unidad</label><select name="unit_id" class="select-field"><option value="">Base</option>@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->abbreviation }}</option>@endforeach</select></div>
        <div><label class="form-label">Precio C$</label><input type="number" step="0.01" name="unit_price" class="input-field" required></div>
        <div><button class="btn-primary w-full">Agregar</button></div>
    </form>
    <div class="card overflow-hidden">
        <table class="w-full table-agro text-sm">
            <thead><tr><th>Producto</th><th>Unidad</th><th class="text-right">Precio</th><th></th></tr></thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->unit?->abbreviation ?? 'Base' }}</td>
                    <td class="text-right font-semibold">C$ {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('inventario.price-lists.items.destroy', [$priceList, $item]) }}" onsubmit="return confirm('¿Eliminar?')">@csrf @method('DELETE')<button class="text-red-600 text-xs">Quitar</button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-8 text-slate-400">Sin precios configurados</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($items->hasPages())<div class="p-3 border-t">{{ $items->links() }}</div>@endif
    </div>
</div>
@endsection
