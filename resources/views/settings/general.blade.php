@extends('layouts.app')

@section('title', 'Configuración General')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Configuración General</h1>
            <p class="page-subtitle">Datos de empresa, moneda, IVA</p>
        </div>
    </div>

    <form action="{{ route('settings.general') }}" method="POST" class="card p-6 space-y-6">
        @csrf

        {{-- Información de la empresa --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Información de la Empresa</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la Empresa *</label>
                    <input type="text" name="company_name" value="{{ $settings['company_name'] ?? '' }}" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">RUC</label>
                    <input type="text" name="company_ruc" value="{{ $settings['company_ruc'] ?? '' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                    <input type="text" name="company_address" value="{{ $settings['company_address'] ?? '' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                    <input type="text" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}" class="input-field">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="company_email" value="{{ $settings['company_email'] ?? '' }}" class="input-field">
                </div>
            </div>
        </div>

        {{-- Configuración financiera --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Configuración Financiera</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Moneda *</label>
                    <select name="currency" required class="select-field">
                        <option value="C$" {{ ($settings['currency'] ?? '') === 'C$' ? 'selected' : '' }}>Córdoba (C$)</option>
                        <option value="USD" {{ ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' }}>Dólar (USD)</option>
                        <option value="EUR" {{ ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">IVA (%) *</label>
                    <input type="number" name="tax_rate" value="{{ $settings['tax_rate'] ?? 15 }}" required min="0" max="100" step="0.01" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Zona Horaria *</label>
                    <select name="timezone" required class="select-field">
                        <option value="America/Managua" {{ ($settings['timezone'] ?? '') === 'America/Managua' ? 'selected' : '' }}>America/Managua</option>
                        <option value="America/El_Salvador" {{ ($settings['timezone'] ?? '') === 'America/El_Salvador' ? 'selected' : '' }}>America/El_Salvador</option>
                        <option value="America/Guatemala" {{ ($settings['timezone'] ?? '') === 'America/Guatemala' ? 'selected' : '' }}>America/Guatemala</option>
                        <option value="America/Tegucigalpa" {{ ($settings['timezone'] ?? '') === 'America/Tegucigalpa' ? 'selected' : '' }}>America/Tegucigalpa</option>
                        <option value="America/Costa_Rica" {{ ($settings['timezone'] ?? '') === 'America/Costa_Rica' ? 'selected' : '' }}>America/Costa_Rica</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-200">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

@endsection
