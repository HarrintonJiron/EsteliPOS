@extends('layouts.app')

@section('title', 'Módulos del sistema')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="page-title">Módulos del sistema</h1>
        <p class="page-subtitle">Controla disponibilidad, dependencias, orden y acceso por roles.</p>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
        Desactivar un módulo oculta su navegación y bloquea todas sus rutas. Primero desactiva los módulos que dependan de él. Los usuarios sin un rol seleccionado perderán acceso inmediatamente.
    </div>

    <form id="modules-form" action="{{ route('settings.modules.update') }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        @foreach($modules as $module)
            @php
                $selectedRoles = collect(old("modules.{$module->id}.roles", $module->roles->pluck('id')->all()))->map(fn($id) => (int) $id);
                $active = (bool) old("modules.{$module->id}.is_active", $module->is_active);
            @endphp
            <article class="card overflow-hidden" data-module-card>
                <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $active ? 'bg-emerald-100' : 'bg-slate-200' }} text-2xl">{{ $module->icon ?: '🧩' }}</div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-slate-900">{{ $module->name }}</h2>
                                <span class="font-mono text-xs text-slate-400">{{ $module->slug }}</span>
                                @if($module->is_core)<span class="badge-warning">Núcleo protegido</span>@endif
                                <span data-status-label class="{{ $active ? 'badge-success' : 'badge-danger' }}">{{ $active ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $module->description }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                @if(count($module->dependencies ?? []))
                                    <span class="text-slate-500">Requiere:</span>
                                    @foreach($module->dependencies as $dependency)<span class="rounded-full bg-cyan-50 px-2 py-1 text-cyan-800">{{ $moduleNames[$dependency] ?? $dependency }}</span>@endforeach
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-600">Sin dependencias</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid shrink-0 grid-cols-2 gap-3 sm:flex sm:items-center">
                        <div><label class="mb-1 block text-xs text-slate-500">Orden</label><input type="number" name="modules[{{ $module->id }}][sort_order]" value="{{ old("modules.{$module->id}.sort_order", $module->sort_order) }}" min="0" max="999" class="input-field w-full text-center sm:w-20"></div>
                        <label class="mt-5 flex items-center justify-end gap-2 text-sm font-medium">
                            <input type="hidden" name="modules[{{ $module->id }}][is_active]" value="0">
                            <input data-module-toggle type="checkbox" name="modules[{{ $module->id }}][is_active]" value="1" class="h-5 w-5 rounded text-emerald-600" @checked($active) @disabled($module->is_core)>
                            Habilitado
                        </label>
                        @if($module->is_core)<input type="hidden" name="modules[{{ $module->id }}][is_active]" value="1">@endif
                    </div>
                </div>

                <details class="border-t border-slate-100 px-5 py-4" @if($errors->any()) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-indigo-700">Acceso por roles · {{ $selectedRoles->count() }} seleccionado(s)</summary>
                    <p class="mt-2 text-xs text-slate-500">Administrador conserva acceso a módulos activos. Para los demás usuarios se requiere al menos uno de estos roles.</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-2.5 text-sm">
                                <input type="checkbox" name="modules[{{ $module->id }}][roles][]" value="{{ $role->id }}" class="rounded" @checked($selectedRoles->contains($role->id))>
                                <span>{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </details>
            </article>
        @endforeach

        <div class="sticky bottom-4 flex justify-end"><button class="btn-primary shadow-lg">Revisar y guardar cambios</button></div>
    </form>
</div>

<dialog id="modules-confirmation" class="w-[calc(100%-2rem)] max-w-lg rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50">
    <div class="p-6">
        <h2 class="text-lg font-bold text-slate-900">Confirmar configuración de módulos</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Los cambios se aplicarán inmediatamente al menú y a las rutas abiertas por todos los usuarios. Las dependencias se validarán antes de guardar.</p>
        <div class="mt-6 flex justify-end gap-2"><button type="button" class="btn-outline" onclick="this.closest('dialog').close()">Cancelar</button><button id="confirm-modules" type="button" class="btn-primary">Aplicar cambios</button></div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-module-toggle]').forEach(toggle => toggle.addEventListener('change', () => {
    const label = toggle.closest('[data-module-card]').querySelector('[data-status-label]');
    label.textContent = toggle.checked ? 'Activo' : 'Inactivo';
    label.className = toggle.checked ? 'badge-success' : 'badge-danger';
}));
const modulesForm = document.getElementById('modules-form');
const modulesDialog = document.getElementById('modules-confirmation');
modulesForm?.addEventListener('submit', event => { event.preventDefault(); modulesDialog.showModal(); });
document.getElementById('confirm-modules')?.addEventListener('click', () => modulesForm.submit());
</script>
@endpush
