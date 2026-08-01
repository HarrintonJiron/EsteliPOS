@extends('layouts.app')

@section('title', 'Comparar roles')

@section('content')
<div class="space-y-6">
    <div><h1 class="page-title">Comparar roles</h1><p class="page-subtitle">Selecciona entre 2 y 4 perfiles para detectar diferencias de acceso.</p></div>

    <form class="card p-5" method="GET">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($availableRoles as $available)
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm">
                    <input type="checkbox" name="roles[]" value="{{ $available->id }}" class="rounded" @checked(request()->collect('roles')->contains((string) $available->id))>
                    {{ $available->name }}
                </label>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end gap-2"><a class="btn-outline" href="{{ route('settings.roles') }}">Volver</a><button class="btn-primary">Comparar seleccionados</button></div>
    </form>

    @if($roles->count() >= 2)
        <div class="card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-800 text-white"><tr><th class="sticky left-0 z-10 bg-slate-800 px-4 py-3 text-left">Permiso</th>@foreach($roles as $role)<th class="min-w-36 px-4 py-3 text-center">{{ $role->name }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($permissionsByModule as $module => $permissions)
                        <tr><td colspan="{{ $roles->count() + 1 }}" class="bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">{{ str_replace('_', ' ', $module) }}</td></tr>
                        @foreach($permissions as $permission)
                            <tr class="border-t border-slate-100">
                                <td class="sticky left-0 bg-white px-4 py-3"><span class="font-medium">{{ $permission->name }}</span><span class="block text-xs text-slate-400">{{ $permission->slug }}</span></td>
                                @foreach($roles as $role)
                                    <td class="px-4 py-3 text-center">
                                        @if($role->permissions->contains('id', $permission->id))<span class="text-lg text-emerald-600" aria-label="Concedido">✓</span>@else<span class="text-slate-300" aria-label="No concedido">—</span>@endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card p-10 text-center text-slate-500">Selecciona al menos dos roles para generar la comparación.</div>
    @endif
</div>
@endsection
