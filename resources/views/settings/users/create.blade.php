@extends('layouts.app')

@section('title', 'Crear Usuario')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Crear Usuario</h1>
            <p class="page-subtitle">Agrega un nuevo usuario al sistema</p>
        </div>
        <a href="{{ route('settings.users') }}" class="btn-outline text-sm">Volver</a>
    </div>

    <form action="{{ route('settings.users.store') }}" method="POST" class="card p-6 space-y-6">
        @csrf

        {{-- Información básica --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                <input type="text" name="name" required class="input-field" placeholder="Nombre completo" autofocus>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                <input type="email" name="email" required class="input-field" placeholder="correo@ejemplo.com">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña *</label>
                <input type="password" name="password" required minlength="8" class="input-field" placeholder="Mínimo 8 caracteres">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar Contraseña *</label>
                <input type="password" name="password_confirmation" required minlength="8" class="input-field" placeholder="Repite la contraseña">
            </div>
        </div>

        {{-- Roles --}}
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-3">Roles</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($roles as $role)
                <label class="flex items-center gap-2 cursor-pointer p-2 border border-slate-200 rounded-lg hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded border-slate-300 text-indigo-600">
                    <span class="text-sm text-slate-700">{{ $role->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
            <a href="{{ route('settings.users') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">Crear Usuario</button>
        </div>
    </form>
</div>

@endsection
