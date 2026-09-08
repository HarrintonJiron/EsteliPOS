@extends('layouts.app')

@section('title', 'Matriz global de permisos')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title">Matriz global de permisos</h1>
            <p class="page-subtitle">Mapa de acceso efectivo por rol, módulo y acción.</p>
        </div>
        <a class="btn-outline justify-center" href="{{ route('settings.roles.compare') }}">Comparar roles</a>
    </div>

    <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
        La matriz es de consulta. Para cambiar una columna, abre el rol correspondiente; cada modificación queda auditada.
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-800 text-white">
                <tr>
                    <th class="sticky left-0 z-20 min-w-64 bg-slate-800 px-4 py-3 text-left">Módulo / permiso</th>
                    @foreach($roles as $role)
                        <th class="min-w-32 px-3 py-3 text-center">
                            <a class="hover:underline" href="{{ route('settings.roles.edit', $role) }}">{{ $role->name }}</a>
                            <span class="mt-1 block text-[10px] font-normal text-slate-300">{{ $role->slug }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissionsByModule as $module => $permissions)
                    <tr>
                        <td colspan="{{ $roles->count() + 1 }}" class="bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">
                            {{ str_replace('_', ' ', $module) }} · {{ $permissions->count() }}
                        </td>
                    </tr>
                    @foreach($permissions as $permission)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="sticky left-0 bg-white px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $permission->name }}</p>
                                <p class="text-xs text-slate-400">{{ $permission->slug }}</p>
                            </td>
                            @foreach($roles as $role)
                                <td class="px-3 py-3 text-center">
                                    @if($role->permissions->contains('id', $permission->id))
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700" aria-label="Concedido">✓</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
