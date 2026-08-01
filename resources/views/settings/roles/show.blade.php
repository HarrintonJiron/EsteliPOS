@extends('layouts.app')

@section('title', 'Ver Rol')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">{{ $role->name }}</h1>
            <p class="page-subtitle">{{ $role->description ?? 'Sin descripción' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('settings.roles') }}" class="btn-outline text-sm">Volver</a>
            @if(!$role->is_system)
            <a href="{{ route('settings.roles.edit', $role) }}" class="btn-primary text-sm">Editar</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Información del rol --}}
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Información</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-slate-500">Nombre</p>
                    <p class="font-medium text-slate-900">{{ $role->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Slug</p>
                    <p class="font-medium text-slate-900">{{ $role->slug }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Descripción</p>
                    <p class="text-sm text-slate-600">{{ $role->description ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Tipo</p>
                    @if($role->is_system)
                        <span class="badge-secondary">Sistema</span>
                    @else
                        <span class="badge-success">Personalizado</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Estadísticas --}}
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Estadísticas</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-slate-500">Usuarios asignados</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ $role->users->count() }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Permisos asignados</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $role->permissions->count() }}</p>
                </div>
            </div>
        </div>

        {{-- Usuarios --}}
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Usuarios</h3>
            @if($role->users->count() > 0)
            <div class="space-y-2">
                @foreach($role->users as $user)
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center">
                        <span class="text-xs font-medium text-slate-600">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500">No hay usuarios asignados</p>
            @endif
        </div>
    </div>

    {{-- Permisos --}}
    <div class="card">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Permisos</h3>
        </div>
        <div class="p-4">
            @if($role->permissions->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($role->permissions->groupBy('module') as $module => $modulePermissions)
                <div class="border border-slate-200 rounded-xl p-4">
                    <h4 class="font-medium text-slate-800 mb-3">{{ ucfirst($module) }}</h4>
                    <div class="space-y-1">
                        @foreach($modulePermissions as $permission)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm text-slate-700">{{ ucfirst($permission->action) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500 text-center py-4">Este rol no tiene permisos asignados</p>
            @endif
        </div>
    </div>
</div>

@endsection
