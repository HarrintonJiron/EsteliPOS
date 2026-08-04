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

    <div class="card p-4 md:p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Presentación de impuesto en facturas y documentos impresos</h2>
                <p class="text-sm text-slate-500 mt-1">Esta configuración es global y solo afecta la visualización en impresión/PDF, no los cálculos contables internos.</p>
            </div>
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.edit'))
                <form method="POST" action="{{ route('settings.taxes.display-mode.update') }}" class="w-full md:w-auto md:min-w-[28rem] grid grid-cols-1 md:grid-cols-3 gap-2">
                    @csrf
                    <select name="invoice_tax_display_mode" class="select-field md:col-span-2" required>
                        @foreach($taxDisplayModes as $mode => $label)
                            <option value="{{ $mode }}" @selected(old('invoice_tax_display_mode', $taxDisplayMode) === $mode)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary">Guardar modo</button>
                </form>
            @else
                <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    Modo activo: <span class="font-semibold">{{ $taxDisplayModes[$taxDisplayMode] ?? 'IVA General (15%)' }}</span>
                </div>
            @endif
        </div>
        @error('invoice_tax_display_mode')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

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
