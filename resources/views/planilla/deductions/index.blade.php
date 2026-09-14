@extends('layouts.app')

@section('content')

<div class="space-y-6">
    @include('planilla._nav')

    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Gestión de Deducciones</h1>
            <p class="page-subtitle">Deducciones aprobadas se restan de la nómina del período</p>
        </div>

        <a href="{{ route('deductions.create') }}" class="btn-primary">
            + Nueva Deducción
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
                @forelse($deductions as $deduction)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ $deduction->employee->name }}</td>
                    <td class="px-6 py-4">{{ $deduction->type_label }}</td>
                    <td class="px-6 py-4 text-red-600 font-medium">C$ {{ number_format($deduction->amount, 2) }}</td>
                    <td class="px-6 py-4">{{ $deduction->date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">{{ $deduction->reason ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs bg-{{ $deduction->status_color }}-100 text-{{ $deduction->status_color }}-700">
                            {{ $deduction->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-2">
                        @if($deduction->status === 'pending')
                            <form action="{{ route('deductions.approve', $deduction) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('¿Aprobar esta deducción?')" class="text-green-600 hover:underline">
                                    Aprobar
                                </button>
                            </form>
                            <a href="{{ route('deductions.edit', $deduction) }}" class="text-indigo-600 hover:underline">
                                Editar
                            </a>
                        @endif
                        @if($deduction->status === 'approved')
                            <form action="{{ route('deductions.mark-deducted', $deduction) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('¿Marcar como deducida?')" class="text-green-600 hover:underline">
                                    Marcar Deducida
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('deductions.show', $deduction) }}" class="text-blue-600 hover:underline">
                            Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No hay deducciones registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>

    </div>

</div>

@endsection
