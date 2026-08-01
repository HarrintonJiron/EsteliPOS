@extends('layouts.app')

@section('title', 'Centros de Costo')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Contabilidad · Centros de Costo</h1>
            <p class="page-subtitle">Sucursales, departamentos, proyectos y áreas para análisis de gastos</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('contabilidad.centros-costo.create') }}" class="btn-primary">+ Nuevo Centro de Costo</a>
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
        <button type="submit" class="btn-primary md:w-fit">Filtrar</button>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($costCenters as $costCenter)
                <tr>
                    <td class="font-mono">{{ $costCenter->code }}</td>
                    <td class="font-semibold text-slate-800">{{ $costCenter->name }}</td>
                    <td class="text-sm">{{ $costCenter->type_label }}</td>
                    <td>
                        @if($costCenter->is_active)
                            <span class="badge-success">Activo</span>
                        @else
                            <span class="badge-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-center whitespace-nowrap">
                        <a href="{{ route('contabilidad.centros-costo.edit', $costCenter) }}" class="text-indigo-600 hover:underline text-sm">Editar</a>
                        <form action="{{ route('contabilidad.centros-costo.destroy', $costCenter) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este centro de costo?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline text-sm ml-2">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-slate-500 py-8">No hay centros de costo registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
