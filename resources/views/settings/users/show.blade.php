@extends('layouts.app')

@section('title', 'Ver Usuario')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">{{ $user->name }}</h1>
            <p class="page-subtitle">{{ $user->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('settings.users') }}" class="btn-outline text-sm">Volver</a>
            @if($user->id !== auth()->id())
            <a href="{{ route('settings.users.edit', $user) }}" class="btn-primary text-sm">Editar</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Información del usuario --}}
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Información</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-slate-500">Nombre</p>
                    <p class="font-medium text-slate-900">{{ $user->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Email</p>
                    <p class="font-medium text-slate-900">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Estado</p>
                    @if($user->is_active)
                        <span class="badge-success">Activo</span>
                    @else
                        <span class="badge-danger">Inactivo</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-slate-500">Último Acceso</p>
                    <p class="text-sm text-slate-600">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca' }}</p>
                </div>
            </div>
        </div>

        {{-- Roles --}}
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Roles</h3>
            @if($user->roles->count() > 0)
            <div class="space-y-2">
                @foreach($user->roles as $role)
                <div class="flex items-center gap-2 p-2 border border-slate-200 rounded-lg">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-slate-900">{{ $role->name }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500">Sin roles asignados</p>
            @endif
        </div>

        {{-- Permisos --}}
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Permisos Totales</h3>
            @if($user->roles->count() > 0)
            <div class="space-y-2">
                @foreach($user->roles as $role)
                    @foreach($role->permissions->groupBy('module') as $module => $modulePermissions)
                        @if($loop->first)
                        <div class="text-xs text-slate-500 font-medium">{{ ucfirst($module) }}</div>
                        @endif
                    @endforeach
                @endforeach
                <p class="text-sm text-slate-600">{{ $user->roles->flatMap->permissions->unique('id')->count() }} permisos asignados</p>
            </div>
            @else
            <p class="text-sm text-slate-500">Sin permisos</p>
            @endif
        </div>
    </div>

    {{-- Acciones --}}
    @if($user->id !== auth()->id())
    <div class="card">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Acciones</h3>
        </div>
        <div class="p-4 flex gap-2">
            <form action="{{ route('settings.users.toggle-active', $user) }}" method="POST">
                @csrf
                <button type="submit" class="btn-{{ $user->is_active ? 'secondary' : 'primary' }}">
                    {{ $user->is_active ? 'Desactivar Usuario' : 'Activar Usuario' }}
                </button>
            </form>
            <form action="{{ route('settings.users.reset-password', $user) }}" method="POST" onsubmit="return confirm('¿Restablecer contraseña? Se generará una nueva aleatoria.')">
                @csrf
                <button type="submit" class="btn-outline">Restablecer Contraseña</button>
            </form>
            <form action="{{ route('settings.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">Eliminar Usuario</button>
            </form>
        </div>
    </div>
    @endif
</div>

@endsection
