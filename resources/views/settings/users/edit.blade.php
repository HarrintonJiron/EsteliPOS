@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Editar Usuario</h1>
            <p class="page-subtitle">Modifica la información del usuario</p>
        </div>
        <a href="{{ route('settings.users') }}" class="btn-outline text-sm">Volver</a>
    </div>

    <form action="{{ route('settings.users.update', $user) }}" method="POST" class="card p-6 space-y-6">
        @csrf
        @method('PATCH')

        {{-- Información básica --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                <input type="text" name="name" value="{{ $user->name }}" required class="input-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                <input type="email" name="email" value="{{ $user->email }}" required class="input-field">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                <input type="password" name="password" minlength="8" class="input-field" placeholder="Dejar en blanco para mantener actual">
                <p class="text-xs text-slate-400 mt-1">Mínimo 8 caracteres</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" minlength="8" class="input-field" placeholder="Repite la nueva contraseña">
            </div>
        </div>

        {{-- Roles --}}
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-3">Roles</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($roles as $role)
                <label class="flex items-center gap-2 cursor-pointer p-2 border border-slate-200 rounded-lg hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                           {{ $user->roles->contains('id', $role->id) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-indigo-600">
                    <span class="text-sm text-slate-700">{{ $role->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
            <a href="{{ route('settings.users') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">Actualizar Usuario</button>
        </div>
    </form>
</div>

@endsection
