@extends('layouts.app')

@section('title', 'Nuevo Tipo de Cambio')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('settings.index') }}" class="hover:text-indigo-600">Configuración</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('settings.exchange-rates.index') }}" class="hover:text-indigo-600">Tipos de Cambio</a>
        <span aria-hidden="true">/</span>
        <span class="font-medium text-slate-800" aria-current="page">Nuevo</span>
    </nav>

    <div>
        <h1 class="page-title">Nuevo Tipo de Cambio</h1>
        <p class="page-subtitle">Configure una nueva tasa de conversión entre monedas.</p>
    </div>

    <form action="{{ route('settings.exchange-rates.store') }}" method="POST" class="card p-6 space-y-6">
        @csrf
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="from_currency" class="mb-1 block text-sm font-medium text-slate-700">Moneda Origen *</label>
                <select id="from_currency" name="from_currency" required class="select-field">
                    <option value="">Seleccionar...</option>
                    <option value="NIO" @selected(old('from_currency') === 'NIO')">Córdoba (NIO)</option>
                    <option value="USD" @selected(old('from_currency') === 'USD')">Dólar (USD)</option>
                    <option value="EUR" @selected(old('from_currency') === 'EUR')">Euro (EUR)</option>
                </select>
                @error('from_currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            
            <div>
                <label for="to_currency" class="mb-1 block text-sm font-medium text-slate-700">Moneda Destino *</label>
                <select id="to_currency" name="to_currency" required class="select-field">
                    <option value="">Seleccionar...</option>
                    <option value="NIO" @selected(old('to_currency') === 'NIO')">Córdoba (NIO)</option>
                    <option value="USD" @selected(old('to_currency') === 'USD')">Dólar (USD)</option>
                    <option value="EUR" @selected(old('to_currency') === 'EUR')">Euro (EUR)</option>
                </select>
                @error('to_currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="rate" class="mb-1 block text-sm font-medium text-slate-700">Tasa de Conversión *</label>
            <input id="rate" type="number" name="rate" step="0.000001" min="0.000001" max="999999.999999" value="{{ old('rate') }}" required class="input-field" placeholder="Ej: 36.500000">
            <p class="mt-1 text-xs text-slate-500">Indica cuántas unidades de la moneda destino equivalen a 1 unidad de la moneda origen.</p>
            @error('rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="effective_date" class="mb-1 block text-sm font-medium text-slate-700">Fecha de Vigencia</label>
            <input id="effective_date" type="date" name="effective_date" value="{{ old('effective_date', now()->toDateString()) }}" class="input-field">
            <p class="mt-1 text-xs text-slate-500">Fecha desde la cual esta tasa estará vigente. Por defecto es hoy.</p>
            @error('effective_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm font-medium text-slate-700">Activo</span>
            </label>
            <p class="mt-1 text-xs text-slate-500">Si está activo, esta tasa se usará para las conversiones.</p>
        </div>

        <div class="flex gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('settings.exchange-rates.index') }}" class="btn-outline flex-1 justify-center">Cancelar</a>
            <button type="submit" class="btn-primary flex-1 justify-center">Guardar Tipo de Cambio</button>
        </div>
    </form>
</div>
@endsection
