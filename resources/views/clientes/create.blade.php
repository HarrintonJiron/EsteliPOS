@extends('layouts.app')

@section('title', 'Crear Cliente')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Registro Completo (Pro)</h1>
            <p class="page-subtitle">Datos de facturación, RUC y configuración de crédito</p>
        </div>
        <a href="{{ route('clientes.index') }}" class="btn-outline text-sm">← Volver</a>
    </div>

    <form action="{{ route('clientes.store') }}" method="POST" class="space-y-4">
        @csrf

        <div class="card p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">Datos básicos</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nombre *</label>
                    <input name="name" value="{{ old('name') }}" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Teléfono</label>
                    <input name="phone" value="{{ old('phone') }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Código interno</label>
                    <input name="code" value="{{ old('code') }}" class="input-field" placeholder="CL-001">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" class="input-field">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-600 mb-1">Razón social</label>
                    <input name="business_name" value="{{ old('business_name') }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">RUC</label>
                    <input name="ruc" value="{{ old('ruc') }}" class="input-field" placeholder="001-123456-0000A">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-600 mb-1">Dirección</label>
                    <textarea name="address" rows="2" class="input-field">{{ old('address') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card p-5 space-y-4 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Configuración de Crédito</h2>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="credit_enabled" value="1" {{ old('credit_enabled') ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-sm">Habilitar crédito</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Límite de crédito (C$)</label>
                    <input type="number" name="credit_limit" step="0.01" min="0" value="{{ old('credit_limit', 5000) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">Dejar en 0 para crédito sin tope</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Plazo de pago (días)</label>
                    <input type="number" name="credit_days" min="1" max="365" value="{{ old('credit_days', 30) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">Fecha límite al facturar a crédito</p>
                </div>
            </div>
        </div>

        {{-- MORA --}}
        <div class="card p-5 space-y-4 border-l-4 border-red-400">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-800">Cargo por Mora</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Se calcula sobre facturas vencidas. Se muestra en el estado de cuenta pero debe cobrarse manualmente.</p>
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="mora_enabled" value="1" {{ old('mora_enabled') ? 'checked' : '' }} class="rounded text-red-500">
                    <span class="text-sm">Habilitar mora</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">% mora por día vencido</label>
                    <input type="number" name="mora_rate" step="0.01" min="0" max="100"
                           value="{{ old('mora_rate', 0.5) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">Ej: 0.5 = 0.5% diario</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Días de gracia</label>
                    <input type="number" name="mora_grace_days" min="0" max="90"
                           value="{{ old('mora_grace_days', 0) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">Días extra antes de aplicar mora</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Mora máxima (% del principal)</label>
                    <input type="number" name="mora_max_pct" step="0.01" min="0" max="100"
                           value="{{ old('mora_max_pct', 30) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">0 = sin tope de mora</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('clientes.index') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">Guardar Cliente</button>
        </div>
    </form>
</div>
@endsection
