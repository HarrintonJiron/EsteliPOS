@extends('layouts.app')

@section('content')

<div class="space-y-6">
    @include('planilla._nav')

    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Gestión de Bonos e Incentivos</h1>
            <p class="page-subtitle">Bonos aprobados se suman al bruto de la nómina del mes</p>
        </div>

        <a href="{{ route('bonuses.create') }}" class="btn-primary">
            + Nuevo Bono
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 uppercase text-xs text-gray-600">
                <tr>
                    <th class="px-6 py-3">Empleado</th>
                    <th class="px-6 py-3">Tipo</th>
                    <th class="px-6 py-3">Monto</th>
                    <th class="px-6 py-3">Fecha</th>
                    <th class="px-6 py-3">Razón</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse($bonuses as $bonus)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ $bonus->employee->name }}</td>
                    <td class="px-6 py-4">{{ $bonus->type_label }}</td>
                    <td class="px-6 py-4 text-green-700 font-medium">C$ {{ number_format($bonus->amount, 2) }}</td>
                    <td class="px-6 py-4">{{ $bonus->date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">{{ $bonus->reason ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs bg-{{ $bonus->status_color }}-100 text-{{ $bonus->status_color }}-700">
                            {{ $bonus->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-2">
                        @if($bonus->status === 'pending')
                            <form action="{{ route('bonuses.approve', $bonus) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('¿Aprobar este bono?')" class="text-green-600 hover:underline">
                                    Aprobar
                                </button>
                            </form>
                            <a href="{{ route('bonuses.edit', $bonus) }}" class="text-indigo-600 hover:underline">
                                Editar
                            </a>
                        @endif
                        @if($bonus->status === 'approved')
                            <form action="{{ route('bonuses.mark-paid', $bonus) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('¿Marcar como pagado?')" class="text-green-600 hover:underline">
                                    Marcar Pagado
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('bonuses.show', $bonus) }}" class="text-blue-600 hover:underline">
                            Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No hay bonos registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>

    </div>

</div>

@endsection
