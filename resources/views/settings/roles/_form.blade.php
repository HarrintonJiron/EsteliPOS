@php
    $editing = isset($role);
    $selected = collect(old('permissions', $editing ? $role->permissions->pluck('id')->all() : []))->map(fn($id)=>(int)$id);
    $adminProtected = $editing && $role->slug === 'admin';
@endphp

<div class="space-y-6" data-permission-matrix>
    <section class="card p-5 sm:p-6">
        @if($editing && $role->is_system)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><strong>Rol del sistema.</strong> Su identidad está protegida; puedes ajustar su descripción y permisos.</div>
        @endif
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium">Nombre *</label><input class="input-field" name="name" value="{{ old('name',$role->name ?? '') }}" required {{ ($role->is_system ?? false) ? 'readonly' : '' }}></div>
            <div><label class="mb-1 block text-sm font-medium">Slug *</label><input class="input-field" name="slug" value="{{ old('slug',$role->slug ?? '') }}" pattern="[a-z0-9_-]+" required {{ ($role->is_system ?? false) ? 'readonly' : '' }}><p class="mt-1 text-xs text-slate-500">Minúsculas, números, guiones y guiones bajos.</p></div>
        </div>
        <div class="mt-4"><label class="mb-1 block text-sm font-medium">Descripción</label><textarea class="input-field" name="description" rows="2">{{ old('description',$role->description ?? '') }}</textarea></div>
    </section>

    <section class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-semibold text-slate-900">Matriz de permisos</h2><p class="text-xs text-slate-500">Los permisos se aplican en backend; marcar una casilla cambia el acceso real.</p></div>@unless($adminProtected)<div class="flex gap-2"><button type="button" class="btn-outline text-xs" data-select-all>Seleccionar todo</button><button type="button" class="btn-outline text-xs" data-clear-all>Limpiar</button></div>@endunless</div>
        @if($adminProtected)<div class="m-5 rounded-xl bg-indigo-50 p-4 text-sm text-indigo-900">Administrador conserva automáticamente todos los permisos para evitar bloqueos del sistema.</div>@endif
        <div class="divide-y divide-slate-100">
            @foreach($permissionsByModule as $module => $permissions)
            <div class="p-5" data-module-group><div class="mb-3 flex items-center justify-between"><h3 class="font-semibold capitalize text-slate-800">{{ str_replace('_',' ',$module) }}</h3>@unless($adminProtected)<button type="button" class="text-xs font-medium text-indigo-600" data-select-module>Seleccionar módulo</button>@endunless</div>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">@foreach($permissions as $permission)<label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="mt-0.5 rounded permission-checkbox" @checked($adminProtected || $selected->contains($permission->id)) @disabled($adminProtected)><span><span class="block text-sm font-medium">{{ $permission->name }}</span><span class="text-xs text-slate-500">{{ $permission->action }}</span></span></label>@endforeach</div>
            </div>
            @endforeach
        </div>
    </section>
    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><a class="btn-outline justify-center" href="{{ $editing ? route('settings.roles.show',$role) : route('settings.roles') }}">Cancelar</a><button class="btn-primary justify-center">{{ $editing ? 'Guardar matriz' : 'Crear rol' }}</button></div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-permission-matrix]').forEach(matrix => {
    const boxes = () => [...matrix.querySelectorAll('.permission-checkbox:not(:disabled)')];
    matrix.querySelector('[data-select-all]')?.addEventListener('click', () => boxes().forEach(box => box.checked = true));
    matrix.querySelector('[data-clear-all]')?.addEventListener('click', () => boxes().forEach(box => box.checked = false));
    matrix.querySelectorAll('[data-select-module]').forEach(button => button.addEventListener('click', () => button.closest('[data-module-group]').querySelectorAll('.permission-checkbox:not(:disabled)').forEach(box => box.checked = true)));
});
</script>
@endpush
