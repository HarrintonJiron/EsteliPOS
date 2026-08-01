@extends('layouts.app')

@section('title', 'Roles')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Roles</h1>
            <p class="page-subtitle">Crear y gestionar roles del sistema</p>
        </div>
        <a href="{{ route('settings.roles.create') }}" class="btn-primary">+ Nuevo Rol</a>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Descripción</th>
                    <th class="text-center">Usuarios</th>
                    <th class="text-center">Permisos</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr>
                    <td>
                        <p class="font-semibold text-slate-900">{{ $role->name }}</p>
                        @if($role->is_system)
                            <span class="badge-secondary text-xs">Sistema</span>
                        @endif
                    </td>
                    <td class="text-sm text-slate-600">{{ $role->slug }}</td>
                    <td class="text-sm text-slate-500">{{ $role->description ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge-info">{{ $role->users_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge-success">{{ $role->permissions->count() }}</span>
                    </td>
                    <td class="text-center space-x-2">
                        <a href="{{ route('settings.roles.show', $role) }}" class="text-indigo-600 text-sm font-medium">Ver</a>
                        @if(!$role->is_system)
                            <a href="{{ route('settings.roles.edit', $role) }}" class="text-slate-500 text-sm">Editar</a>
                            <form action="{{ route('settings.roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este rol?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 text-sm hover:text-red-800">Eliminar</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-8 text-slate-500">No hay roles registrados</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">{{ $roles->links() }}</div>
    </div>
</div>

@endsection
