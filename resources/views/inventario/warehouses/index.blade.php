@extends('layouts.app')
@section('title', 'Bodegas')
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="page-title">Bodegas</h1>
            <p class="page-subtitle">Existencias por ubicación física · el POS puede vender desde cualquier bodega con stock</p>
        </div>
        <a href="{{ route('inventario.warehouses.create') }}" class="btn-primary text-sm">+ Nueva bodega</a>
    </div>

    @if(session('success'))
        <div class="card p-3 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="card p-3 bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Bodegas activas</p>
            <p class="mt-1 text-2xl font-bold text-slate-800">{{ $stats['active'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Productos con stock</p>
            <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $stats['products_with_stock'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Valor estimado</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">C$ {{ number_format($stats['estimated_value'], 2) }}</p>
        </div>
    </div>

    <div class="card p-4 bg-slate-50 border border-slate-200">
        <h2 class="font-semibold text-slate-800 text-sm">Cómo funciona</h2>
        <ul class="mt-2 text-sm text-slate-600 space-y-1 list-disc list-inside">
            <li>Cada producto puede tener stock en una o varias bodegas.</li>
            <li>En el POS, si dejas la bodega en <strong>Automática</strong>, se descuenta de donde haya existencias.</li>
            <li>Las compras y ajustes deben indicar a qué bodega entra o sale el stock.</li>
            <li>Usa <strong>Transferir</strong> dentro de cada bodega para mover productos entre ubicaciones.</li>
        </ul>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($warehouses as $wh)
        <div class="card p-4 flex flex-col gap-3 {{ $wh->is_active ? '' : 'opacity-60' }}">
            <div class="flex justify-between items-start gap-2">
                <div>
                    <p class="font-mono text-xs text-slate-500">{{ $wh->code }}</p>
                    <h3 class="font-bold text-slate-900">{{ $wh->name }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $wh->city ?: 'Sin ciudad' }}{{ $wh->address ? ' · '.$wh->address : '' }}</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    @if($wh->is_default)<span class="badge-success text-[10px]">Principal</span>@endif
                    @unless($wh->is_active)<span class="badge-danger text-[10px]">Inactiva</span>@endunless
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg bg-slate-50 p-2">
                    <p class="text-[11px] text-slate-500">Productos</p>
                    <p class="font-semibold text-slate-800">{{ $wh->products_count }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-2">
                    <p class="text-[11px] text-slate-500">Valor est.</p>
                    <p class="font-semibold text-slate-800">C$ {{ number_format($wh->estimated_value, 0) }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-auto">
                <a href="{{ route('inventario.warehouses.show', $wh) }}" class="btn-primary btn-sm">Ver stock</a>
                <a href="{{ route('inventario.warehouses.edit', $wh) }}" class="btn-outline btn-sm">Editar</a>
            </div>
        </div>
        @endforeach
    </div>

    @if($recentStocks->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Movimientos recientes de stock por bodega</h2>
            <p class="text-xs text-slate-500">Últimas existencias actualizadas</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-agro text-sm">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Bodega</th>
                        <th class="text-right">Cantidad</th>
                        <th>Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentStocks as $stock)
                    <tr>
                        <td class="font-medium">{{ $stock->product?->name ?? '—' }}</td>
                        <td>{{ $stock->warehouse?->name ?? '—' }}</td>
                        <td class="text-right font-semibold">{{ rtrim(rtrim(number_format($stock->quantity, 4, '.', ''), '0'), '.') }}</td>
                        <td class="text-slate-500">{{ $stock->updated_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
