@extends('layouts.app')
@section('title', $warehouse->name)
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')

    <div class="flex flex-wrap justify-between items-start gap-3">
        <div>
            <h1 class="page-title">{{ $warehouse->name }}</h1>
            <p class="page-subtitle">
                {{ $warehouse->code }}
                @if($warehouse->is_default) · Principal @endif
                · {{ $productsCount }} productos
                · Valor estimado C$ {{ number_format($totalValue, 2) }}
            </p>
            @if($warehouse->address || $warehouse->city || $warehouse->phone)
                <p class="text-sm text-slate-500 mt-1">
                    {{ collect([$warehouse->address, $warehouse->city, $warehouse->phone])->filter()->implode(' · ') }}
                </p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('inventario.warehouses.edit', $warehouse) }}" class="btn-outline text-sm">Editar</a>
            <a href="{{ route('inventario.warehouses.index') }}" class="btn-outline text-sm">Todas las bodegas</a>
            @unless($warehouse->is_default)
            <form method="POST" action="{{ route('inventario.warehouses.destroy', $warehouse) }}"
                  onsubmit="return confirm('¿Eliminar esta bodega? Solo si no tiene stock.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-outline text-sm text-rose-600 border-rose-200 hover:bg-rose-50">Eliminar</button>
            </form>
            @endunless
        </div>
    </div>

    @if(session('success'))
        <div class="card p-3 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="card p-3 bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 space-y-4">
            <form method="GET" class="card p-3 flex flex-wrap gap-2 items-center">
                <input type="search" name="q" value="{{ $search }}" placeholder="Buscar producto o código..." class="input-field min-w-0 flex-1 sm:min-w-[200px]">
                <button class="btn-primary text-sm">Buscar</button>
                @if($search !== '')
                    <a href="{{ route('inventario.warehouses.show', $warehouse) }}" class="btn-outline text-sm">Limpiar</a>
                @endif
            </form>

            <div class="card overflow-hidden">
                <table class="w-full table-agro text-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Ubicación</th>
                            <th class="text-right">Stock</th>
                            <th>Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stocks as $stock)
                        <tr>
                            <td>
                                <a href="{{ route('inventario.show', $stock->product_id) }}" class="text-link font-medium">
                                    {{ $stock->product->name }}
                                </a>
                            </td>
                            <td class="font-mono text-xs text-slate-500">{{ $stock->product->code }}</td>
                            <td>{{ $stock->aisle ?: '—' }}</td>
                            <td class="text-right font-semibold">{{ rtrim(rtrim(number_format($stock->quantity, 4, '.', ''), '0'), '.') }}</td>
                            <td>{{ $stock->product->baseUnitLabel() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-slate-400">
                                {{ $search !== '' ? 'Sin resultados para esa búsqueda.' : 'Sin existencias en esta bodega.' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($stocks->hasPages())
                    <div class="p-3 border-t">{{ $stocks->links() }}</div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-4 space-y-3">
                <div>
                    <h2 class="font-semibold text-slate-800">Transferir stock</h2>
                    <p class="text-xs text-slate-500">Mueve existencias desde esta bodega hacia otra.</p>
                </div>

                @if($otherWarehouses->isEmpty() || $transferProducts->isEmpty())
                    <p class="text-sm text-slate-500">
                        @if($transferProducts->isEmpty())
                            No hay productos con stock para transferir.
                        @else
                            Necesitas al menos otra bodega activa como destino.
                        @endif
                    </p>
                @else
                    <form method="POST" action="{{ route('inventario.warehouses.transfer', $warehouse) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="form-label">Producto *</label>
                            <select name="product_id" class="select-field" required>
                                <option value="">Seleccionar…</option>
                                @foreach($transferProducts as $row)
                                    <option value="{{ $row->product_id }}">
                                        {{ $row->product->name }} ({{ rtrim(rtrim(number_format($row->quantity, 4, '.', ''), '0'), '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Bodega destino *</label>
                            <select name="to_warehouse_id" class="select-field" required>
                                <option value="">Seleccionar…</option>
                                @foreach($otherWarehouses as $dest)
                                    <option value="{{ $dest->id }}">{{ $dest->name }} ({{ $dest->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Cantidad *</label>
                            <input type="number" name="quantity" step="0.0001" min="0.0001" class="input-field" required>
                        </div>
                        <div>
                            <label class="form-label">Nota</label>
                            <input type="text" name="note" class="input-field" maxlength="500" placeholder="Opcional">
                        </div>
                        <button type="submit" class="btn-primary w-full justify-center">Transferir</button>
                    </form>
                @endif
            </div>

            <div class="card p-4 bg-indigo-50 border border-indigo-100 text-sm text-indigo-900 space-y-2">
                <p class="font-semibold">Tip POS</p>
                <p>Si este producto está aquí y el cajero deja la bodega en <strong>Automática</strong>, la venta puede salir de esta ubicación sin cambiar el selector.</p>
            </div>
        </div>
    </div>
</div>
@endsection
