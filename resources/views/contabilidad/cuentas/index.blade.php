@extends('layouts.app')

@section('title', 'Catálogo de Cuentas')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Contabilidad · Catálogo de Cuentas</h1>
            <p class="page-subtitle">Estructura de cuentas contables bajo partida doble</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('contabilidad.cuentas.create') }}" class="btn-primary">+ Nueva Cuenta</a>
        </div>
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por código o nombre..." class="input-field md:col-span-2">
        <select name="type" class="select-field">
            <option value="">Todos los tipos</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="status" class="select-field">
                <option value="">Todos los estados</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
            </select>
            <button type="submit" class="btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Naturaleza</th>
                    <th class="text-center">Nivel</th>
                    <th>Cuenta padre</th>
                    <th class="text-center">Postable</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td class="font-mono">
                        <span style="padding-left: {{ ($account->level - 1) * 1.25 }}rem">{{ $account->code }}</span>
                    </td>
                    <td class="font-semibold text-slate-800">{{ $account->name }}</td>
                    <td class="text-sm">{{ $account->type_label }}</td>
                    <td class="text-sm">{{ $account->nature === 'debit' ? 'Deudora' : 'Acreedora' }}</td>
                    <td class="text-center">{{ $account->level }}</td>
                    <td class="text-sm text-slate-500">{{ $account->parent?->name ?? '—' }}</td>
                    <td class="text-center">
                        @if($account->is_postable)
                            <span class="badge-success">Sí</span>
                        @else
                            <span class="badge-info">Grupo</span>
                        @endif
                    </td>
                    <td>
                        @if($account->is_active)
                            <span class="badge-success">Activa</span>
                        @else
                            <span class="badge-danger">Inactiva</span>
                        @endif
                    </td>
                    <td class="text-center whitespace-nowrap">
                        <a href="{{ route('contabilidad.cuentas.edit', $account) }}" class="text-indigo-600 hover:underline text-sm">Editar</a>
                        @if(!$account->is_system)
                            <form action="{{ route('contabilidad.cuentas.destroy', $account) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta cuenta?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm ml-2">Eliminar</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-slate-500 py-8">No hay cuentas registradas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
