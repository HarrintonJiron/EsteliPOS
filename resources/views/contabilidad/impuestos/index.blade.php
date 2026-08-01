@extends('layouts.app')

@section('title', 'Impuestos')

@section('content')

<div class="space-y-6">

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Configuración · Impuestos (IVA)</h1>
            <p class="page-subtitle">Catálogo configurable de tasas de impuesto aplicadas a ventas y compras</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.edit'))
            <a href="{{ route('settings.taxes.create') }}" class="btn-primary">+ Nuevo Impuesto</a>
            @endif
        </div>
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por código o nombre..." class="input-field md:col-span-3">
        <button type="submit" class="btn-primary">Filtrar</button>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th class="text-center">Tasa</th>
                    <th class="text-center">Predeterminado</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($taxes as $tax)
                <tr>
                    <td class="font-mono">{{ $tax->code }}</td>
                    <td class="font-semibold text-slate-800">{{ $tax->name }}</td>
                    <td class="text-center">{{ number_format($tax->rate * 100, 2) }}%</td>
                    <td class="text-center">
                        @if($tax->is_default)
                            <span class="badge-success">Sí</span>
                        @else
                            <span class="badge-info">No</span>
                        @endif
                    </td>
                    <td>
                        @if($tax->is_active)
                            <span class="badge-success">Activo</span>
                        @else
                            <span class="badge-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-center whitespace-nowrap">
                        @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.edit'))
                        <a href="{{ route('settings.taxes.edit', $tax) }}" class="text-indigo-600 hover:underline text-sm">Editar</a>
                        <form action="{{ route('settings.taxes.destroy', $tax) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este impuesto?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline text-sm ml-2">Eliminar</button>
                        </form>
                        @else
                            <span class="text-xs text-slate-400">Solo lectura</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-500 py-6">No hay impuestos configurados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
