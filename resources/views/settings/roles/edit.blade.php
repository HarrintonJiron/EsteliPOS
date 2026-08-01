@extends('layouts.app')

@section('title', 'Editar Rol')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Editar Rol</h1>
            <p class="page-subtitle">Modifica el rol y sus permisos</p>
        </div>
        <a href="{{ route('settings.roles') }}" class="btn-outline text-sm">Volver</a>
    </div>

    <form action="{{ route('settings.roles.update', $role) }}" method="POST" class="card p-6 space-y-6">
        @csrf
        @method('PATCH')

        @if($role->is_system)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
            <strong>⚠️ Rol del sistema:</strong> Este rol es protegido por el sistema. Solo puedes modificar sus permisos.
        </div>
        @endif

        {{-- Información básica --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                <input type="text" name="name" value="{{ $role->name }}" required class="input-field" {{ $role->is_system ? 'readonly' : '' }}>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Slug *</label>
                <input type="text" name="slug" value="{{ $role->slug }}" required pattern="[a-z0-9_-]+" class="input-field" {{ $role->is_system ? 'readonly' : '' }}>
                <p class="text-xs text-slate-400 mt-1">Solo letras minúsculas, números, guiones y guiones bajos</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
            <textarea name="description" rows="2" class="input-field">{{ $role->description ?? '' }}</textarea>
        </div>

        {{-- Permisos --}}
        <div>
            <h3 class="font-semibold text-slate-900 mb-4">Permisos</h3>
            <div class="space-y-4">
                @foreach($permissions as $module => $modulePermissions)
                <div class="border border-slate-200 rounded-xl p-4">
                    <h4 class="font-medium text-slate-800 mb-3">{{ ucfirst($module) }}</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($modulePermissions as $permission)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                   {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-indigo-600">
                            <span class="text-sm text-slate-700">{{ ucfirst($permission->action) }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
            <a href="{{ route('settings.roles') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">Actualizar Rol</button>
        </div>
    </form>
</div>

@endsection
