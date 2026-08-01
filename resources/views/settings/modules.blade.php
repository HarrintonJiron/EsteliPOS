@extends('layouts.app')

@section('title', 'Módulos')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Módulos</h1>
            <p class="page-subtitle">Activar/desactivar módulos del sistema</p>
        </div>
    </div>

    <form action="{{ route('settings.modules') }}" method="POST" class="card p-6 space-y-4">
        @csrf

        @forelse($modules as $module)
        <div class="flex items-center justify-between p-4 border border-slate-200 rounded-xl {{ $module->is_active ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 {{ $module->is_active ? 'bg-emerald-100' : 'bg-slate-200' }} rounded-xl flex items-center justify-center">
                    @if($module->icon)
                        <span class="text-2xl">{{ $module->icon }}</span>
                    @else
                        <svg class="w-6 h-6 {{ $module->is_active ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    @endif
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">{{ $module->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $module->description ?? 'Sin descripción' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <input type="number" name="modules[{{ $module->id }}][sort_order]" value="{{ $module->sort_order }}" min="0" class="w-20 input-field text-center" title="Orden">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="modules[{{ $module->id }}][is_active]" value="1" {{ $module->is_active ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 w-5 h-5">
                    <span class="text-sm font-medium {{ $module->is_active ? 'text-emerald-700' : 'text-slate-500' }}">{{ $module->is_active ? 'Activo' : 'Inactivo' }}</span>
                </label>
            </div>
        </div>
        @empty
        <div class="text-center py-8 text-slate-500">No hay módulos registrados</div>
        @endforelse

        <div class="flex justify-end pt-4 border-t border-slate-200">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

@endsection
