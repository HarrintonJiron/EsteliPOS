@extends('layouts.app')

@section('title', 'Apariencia')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Apariencia</h1>
            <p class="page-subtitle">Tema, colores, logo</p>
        </div>
    </div>

    <form action="{{ route('settings.appearance') }}" method="POST" class="card p-6 space-y-6">
        @csrf

        {{-- Tema --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Tema</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 {{ ($settings['theme'] ?? 'light') === 'light' ? 'bg-indigo-50 border-indigo-500' : '' }}">
                    <input type="radio" name="theme" value="light" {{ ($settings['theme'] ?? 'light') === 'light' ? 'checked' : '' }} class="text-indigo-600">
                    <div>
                        <p class="font-medium text-slate-900">Claro</p>
                        <p class="text-sm text-slate-500">Tema claro por defecto</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 {{ ($settings['theme'] ?? 'light') === 'dark' ? 'bg-indigo-50 border-indigo-500' : '' }}">
                    <input type="radio" name="theme" value="dark" {{ ($settings['theme'] ?? 'light') === 'dark' ? 'checked' : '' }} class="text-indigo-600">
                    <div>
                        <p class="font-medium text-slate-900">Oscuro</p>
                        <p class="text-sm text-slate-500">Tema oscuro</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 {{ ($settings['theme'] ?? 'light') === 'auto' ? 'bg-indigo-50 border-indigo-500' : '' }}">
                    <input type="radio" name="theme" value="auto" {{ ($settings['theme'] ?? 'light') === 'auto' ? 'checked' : '' }} class="text-indigo-600">
                    <div>
                        <p class="font-medium text-slate-900">Automático</p>
                        <p class="text-sm text-slate-500">Según el sistema</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Colores --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Colores</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Color Principal *</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="primary_color_picker" value="{{ $settings['primary_color'] ?? '#6366f1' }}" class="w-12 h-10 rounded border border-slate-300">
                        <input type="text" id="primary_color" name="primary_color" value="{{ $settings['primary_color'] ?? '#6366f1' }}" required pattern="#[0-9a-fA-F]{6}" class="input-field flex-1">
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Color principal de la interfaz</p>
                </div>
            </div>
        </div>

        {{-- Identidad --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Identidad del Sistema</h3>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Sistema *</label>
                <input type="text" name="system_name" value="{{ $settings['system_name'] ?? 'Agroservicio POS' }}" required class="input-field">
                <p class="text-xs text-slate-400 mt-1">Nombre que aparece en el encabezado y título</p>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-200">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const colorPicker = document.getElementById('primary_color_picker');
    const colorText = document.getElementById('primary_color');

    colorPicker?.addEventListener('input', () => colorText.value = colorPicker.value);
    colorText?.addEventListener('input', () => {
        if (/^#[0-9a-fA-F]{6}$/.test(colorText.value)) colorPicker.value = colorText.value;
    });
</script>
@endpush

@endsection
