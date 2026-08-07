@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="page-title">Usuarios</h1><p class="page-subtitle">Cuentas, roles, permisos y actividad de acceso.</p></div>
        <a href="{{ route('settings.users.create') }}" class="btn-primary justify-center">+ Nuevo usuario</a>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach(['total' => 'Total', 'active' => 'Activos', 'inactive' => 'Inactivos'] as $key => $label)
        <div class="card p-4"><p class="text-xs font-medium text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $counts[$key] }}</p></div>
        @endforeach
    </div>

    <form class="card grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_180px_180px_auto]" method="GET">
        <input class="input-field" name="search" value="{{ request('search') }}" placeholder="Buscar nombre, correo, usuario o teléfono">
        <select class="select-field" name="status"><option value="">Todos los estados</option><option value="active" @selected(request('status')==='active')>Activos</option><option value="inactive" @selected(request('status')==='inactive')>Inactivos</option></select>
        <select class="select-field" name="role"><option value="">Todos los roles</option>@foreach($roles as $role)<option value="{{ $role->slug }}" @selected(request('role')===$role->slug)>{{ $role->name }}</option>@endforeach</select>
        <div class="flex gap-2"><button class="btn-primary flex-1 justify-center">Filtrar</button>@if(request()->hasAny(['search','status','role']))<a class="btn-outline" href="{{ route('settings.users') }}">Limpiar</a>@endif</div>
    </form>

    <div class="space-y-3 md:hidden">
        @forelse($users as $user)
        <article class="card p-4"><div class="flex gap-3">
            @if($user->profile_photo)<img src="{{ Storage::disk('public')->url($user->profile_photo) }}" class="h-12 w-12 rounded-xl object-cover" alt="">@else<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 font-bold text-indigo-700">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</div>@endif
            <div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-2"><div><a href="{{ route('settings.users.show',$user) }}" class="font-semibold text-slate-900">{{ $user->name }}</a><p class="truncate text-xs text-slate-500">{{ '@'.($user->username ?: 'sin-usuario') }} · {{ $user->email }}</p></div><span class="{{ $user->is_active ? 'badge-success' : 'badge-danger' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></div>
                <div class="mt-3 flex flex-wrap gap-1">@forelse($user->roles as $role)<span class="badge-info">{{ $role->name }}</span>@empty<span class="text-xs text-slate-400">Sin rol</span>@endforelse</div>
                <div class="mt-4 flex gap-4"><a href="{{ route('settings.users.show',$user) }}" class="text-sm font-medium text-indigo-600">Ver</a><a href="{{ route('settings.users.edit',$user) }}" class="text-sm text-slate-600">Editar</a><a href="{{ route('settings.users.reset-password.form',$user) }}" class="text-sm text-cyan-700">Contraseña</a></div>
            </div>
        </div></article>
        @empty <div class="card p-8 text-center text-slate-500">No se encontraron usuarios.</div> @endforelse
    </div>

    <div class="card hidden overflow-x-auto md:block"><table class="table-agro min-w-full"><thead><tr><th>Usuario</th><th>Roles</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr></thead><tbody>
        @forelse($users as $user)<tr><td><div class="flex items-center gap-3">@if($user->profile_photo)<img src="{{ Storage::disk('public')->url($user->profile_photo) }}" class="h-10 w-10 rounded-xl object-cover" alt="">@else<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 font-bold text-indigo-700">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</div>@endif<div><a href="{{ route('settings.users.show',$user) }}" class="font-semibold text-slate-900">{{ $user->name }}</a><p class="text-xs text-slate-500">{{ $user->username ? '@'.$user->username.' · ' : '' }}{{ $user->email }}</p></div></div></td>
        <td><div class="flex flex-wrap gap-1">@forelse($user->roles as $role)<span class="badge-info">{{ $role->name }}</span>@empty<span class="text-slate-400">Sin rol</span>@endforelse</div></td><td><span class="{{ $user->is_active ? 'badge-success' : 'badge-danger' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td><td>{{ $user->last_login_at?->diffForHumans() ?? 'Nunca' }}</td><td><div class="flex gap-3"><a class="font-medium text-indigo-600" href="{{ route('settings.users.show',$user) }}">Ver</a><a class="text-slate-600" href="{{ route('settings.users.edit',$user) }}">Editar</a><a class="text-cyan-700" href="{{ route('settings.users.reset-password.form',$user) }}">Contraseña</a></div></td></tr>
        @empty<tr><td colspan="5" class="py-10 text-center">No se encontraron usuarios.</td></tr>@endforelse
    </tbody></table></div>
    <div>{{ $users->links() }}</div>
</div>
@endsection
