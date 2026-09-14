@extends('layouts.app')

@section('title', 'Numeraciones')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Numeraciones</h1>
            <p class="page-subtitle">Consecutivos de facturas y documentos</p>
        </div>
    </div>

    <form action="{{ route('settings.sequences') }}" method="POST" class="card p-6 space-y-6">
        @csrf

        {{-- Facturas --}}
        <div class="border border-slate-200 rounded-xl p-4">
            <h3 class="font-semibold text-slate-900 mb-4">Facturas</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prefijo</label>
                    <input type="text" name="sequences[factura][prefix]" value="{{ $sequences['factura']->prefix ?? 'FAC-' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número Actual</label>
                    <input type="number" name="sequences[factura][current_number]" value="{{ $sequences['factura']->current_number ?? 1 }}" min="1" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Relleno (ceros)</label>
                    <input type="number" name="sequences[factura][padding]" value="{{ $sequences['factura']->padding ?? 6 }}" min="1" max="10" class="input-field">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="sequences[factura][is_active]" value="1" {{ ($sequences['factura']->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Activo</label>
                </div>
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Próximo número: <code class="bg-slate-100 px-2 py-1 rounded">{{ $sequences['factura']->prefix ?? 'FAC-' }}{{ str_pad($sequences['factura']->current_number ?? 1, $sequences['factura']->padding ?? 6, '0', STR_PAD_LEFT) }}</code>
            </div>
        </div>

        {{-- Compras --}}
        <div class="border border-slate-200 rounded-xl p-4">
            <h3 class="font-semibold text-slate-900 mb-4">Compras</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prefijo</label>
                    <input type="text" name="sequences[compra][prefix]" value="{{ $sequences['compra']->prefix ?? 'COM-' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número Actual</label>
                    <input type="number" name="sequences[compra][current_number]" value="{{ $sequences['compra']->current_number ?? 1 }}" min="1" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Relleno (ceros)</label>
                    <input type="number" name="sequences[compra][padding]" value="{{ $sequences['compra']->padding ?? 6 }}" min="1" max="10" class="input-field">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="sequences[compra][is_active]" value="1" {{ ($sequences['compra']->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Activo</label>
                </div>
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Próximo número: <code class="bg-slate-100 px-2 py-1 rounded">{{ $sequences['compra']->prefix ?? 'COM-' }}{{ str_pad($sequences['compra']->current_number ?? 1, $sequences['compra']->padding ?? 6, '0', STR_PAD_LEFT) }}</code>
            </div>
        </div>

        {{-- Cotizaciones --}}
        <div class="border border-slate-200 rounded-xl p-4">
            <h3 class="font-semibold text-slate-900 mb-4">Cotizaciones</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prefijo</label>
                    <input type="text" name="sequences[cotizacion][prefix]" value="{{ $sequences['cotizacion']->prefix ?? 'COT-' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número Actual</label>
                    <input type="number" name="sequences[cotizacion][current_number]" value="{{ $sequences['cotizacion']->current_number ?? 1 }}" min="1" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Relleno (ceros)</label>
                    <input type="number" name="sequences[cotizacion][padding]" value="{{ $sequences['cotizacion']->padding ?? 6 }}" min="1" max="10" class="input-field">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="sequences[cotizacion][is_active]" value="1" {{ ($sequences['cotizacion']->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Activo</label>
                </div>
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Próximo número: <code class="bg-slate-100 px-2 py-1 rounded">{{ $sequences['cotizacion']->prefix ?? 'COT-' }}{{ str_pad($sequences['cotizacion']->current_number ?? 1, $sequences['cotizacion']->padding ?? 6, '0', STR_PAD_LEFT) }}</code>
            </div>
        </div>

        {{-- Recibos --}}
        <div class="border border-slate-200 rounded-xl p-4">
            <h3 class="font-semibold text-slate-900 mb-4">Recibos</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prefijo</label>
                    <input type="text" name="sequences[recibo][prefix]" value="{{ $sequences['recibo']->prefix ?? 'REC-' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número Actual</label>
                    <input type="number" name="sequences[recibo][current_number]" value="{{ $sequences['recibo']->current_number ?? 1 }}" min="1" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Relleno (ceros)</label>
                    <input type="number" name="sequences[recibo][padding]" value="{{ $sequences['recibo']->padding ?? 6 }}" min="1" max="10" class="input-field">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="sequences[recibo][is_active]" value="1" {{ ($sequences['recibo']->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Activo</label>
                </div>
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Próximo número: <code class="bg-slate-100 px-2 py-1 rounded">{{ $sequences['recibo']->prefix ?? 'REC-' }}{{ str_pad($sequences['recibo']->current_number ?? 1, $sequences['recibo']->padding ?? 6, '0', STR_PAD_LEFT) }}</code>
            </div>
        </div>

        {{-- Ajustes de Inventario --}}
        <div class="border border-slate-200 rounded-xl p-4">
            <h3 class="font-semibold text-slate-900 mb-4">Ajustes de Inventario</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prefijo</label>
                    <input type="text" name="sequences[ajuste][prefix]" value="{{ $sequences['ajuste']->prefix ?? 'AJU-' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número Actual</label>
                    <input type="number" name="sequences[ajuste][current_number]" value="{{ $sequences['ajuste']->current_number ?? 1 }}" min="1" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Relleno (ceros)</label>
                    <input type="number" name="sequences[ajuste][padding]" value="{{ $sequences['ajuste']->padding ?? 6 }}" min="1" max="10" class="input-field">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="sequences[ajuste][is_active]" value="1" {{ ($sequences['ajuste']->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Activo</label>
                </div>
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Próximo número: <code class="bg-slate-100 px-2 py-1 rounded">{{ $sequences['ajuste']->prefix ?? 'AJU-' }}{{ str_pad($sequences['ajuste']->current_number ?? 1, $sequences['ajuste']->padding ?? 6, '0', STR_PAD_LEFT) }}</code>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-200">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

@endsection
