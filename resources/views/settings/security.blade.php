@extends('layouts.app')

@section('title', 'Seguridad')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Seguridad</h1>
            <p class="page-subtitle">Sesión, contraseñas, 2FA</p>
        </div>
    </div>

    <form action="{{ route('settings.security') }}" method="POST" class="card p-6 space-y-6">
        @csrf

        {{-- Configuración de sesión --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Configuración de Sesión</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tiempo de Sesión (minutos) *</label>
                    <input type="number" name="session_timeout" value="{{ $settings['session_timeout'] ?? 60 }}" required min="5" max="1440" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">5-1440 minutos</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Intentos Máximos de Login *</label>
                    <input type="number" name="max_login_attempts" value="{{ $settings['max_login_attempts'] ?? 5 }}" required min="1" max="10" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">1-10 intentos</p>
                </div>
            </div>
        </div>

        {{-- Política de contraseñas --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Política de Contraseñas</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Longitud Mínima *</label>
                    <input type="number" name="password_min_length" value="{{ $settings['password_min_length'] ?? 8 }}" required min="6" max="20" class="input-field">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="password_require_uppercase" value="1" {{ ($settings['password_require_uppercase'] ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Requerir mayúsculas</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="password_require_lowercase" value="1" {{ ($settings['password_require_lowercase'] ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Requerir minúsculas</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="password_require_numbers" value="1" {{ ($settings['password_require_numbers'] ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Requerir números</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="password_require_special_chars" value="1" {{ ($settings['password_require_special_chars'] ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    <label class="text-sm text-slate-700">Requerir caracteres especiales</label>
                </div>
            </div>
        </div>

        {{-- Autenticación de dos factores --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Autenticación de Dos Factores</h3>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="two_factor_enabled" value="1" {{ ($settings['two_factor_enabled'] ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                <label class="text-sm text-slate-700">Habilitar 2FA (estructura preparada)</label>
            </div>
            <p class="text-xs text-slate-400 mt-1">La implementación completa de 2FA requiere configuración adicional</p>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-200">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

@endsection
