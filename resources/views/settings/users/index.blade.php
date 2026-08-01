@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Gestión de usuarios, roles y accesos</p>
        </div>
        <a href="{{ route('settings.users.create') }}" class="btn-primary">+ Nuevo Usuario</a>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Último Acceso</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                    </td>
                    <td class="text-sm text-slate-600">{{ $user->email }}</td>
                    <td>
                        @if($user->roles->count() > 0)
                            @foreach($user->roles as $role)
                                <span class="badge-info text-xs">{{ $role->name }}</span>
                            @endforeach
                        @else
                            <span class="text-slate-400 text-sm">Sin roles</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($user->is_active)
                            <span class="badge-success">Activo</span>
                        @else
                            <span class="badge-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-center text-sm text-slate-500">
                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca' }}
                    </td>
                    <td class="text-center space-x-2">
                        <a href="{{ route('settings.users.show', $user) }}" class="text-indigo-600 text-sm font-medium">Ver</a>
                        <a href="{{ route('settings.users.edit', $user) }}" class="text-slate-500 text-sm">Editar</a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('settings.users.toggle-active', $user) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-amber-600 text-sm hover:text-amber-800" title="{{ $user->is_active ? 'Desactivar' : 'Activar' }}">
                                    {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                            <form action="{{ route('settings.users.reset-password', $user) }}" method="POST" class="inline" onsubmit="return confirm('¿Restablecer contraseña? Se generará una nueva aleatoria.')">
                                @csrf
                                <button type="submit" class="text-cyan-600 text-sm hover:text-cyan-800" title="Restablecer contraseña">Reset</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-8 text-slate-500">No hay usuarios registrados</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">{{ $users->links() }}</div>
    </div>
</div>

@endsection
