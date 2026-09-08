@extends('layouts.app')

@section('content')

<div class="space-y-6">
    @include('planilla._nav')

    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Gestión de Préstamos y Anticipos</h1>
            <p class="page-subtitle">Solicitudes, saldos y cuotas descontadas en nómina</p>
        </div>

        <a href="{{ route('loans.create') }}" class="btn-primary">
            + Nuevo Préstamo
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
                    <th class="px-6 py-3">Cuota Mensual</th>
                    <th class="px-6 py-3">Saldo</th>
                    <th class="px-6 py-3">Período</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse($loans as $loan)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ $loan->employee?->name ?? 'Empleado no disponible' }}</td>
                    <td class="px-6 py-4">{{ $loan->type_label }}</td>
                    <td class="px-6 py-4">C$ {{ number_format($loan->amount, 2) }}</td>
                    <td class="px-6 py-4">C$ {{ number_format($loan->monthly_payment, 2) }}</td>
                    <td class="px-6 py-4">C$ {{ number_format($loan->remaining_balance, 2) }}</td>
                    <td class="px-6 py-4">
                        {{ $loan->start_date->format('d/m/Y') }} - {{ $loan->end_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs bg-{{ $loan->status_color }}-100 text-{{ $loan->status_color }}-700">
                            {{ $loan->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-2">
                        @if($loan->status === 'pending')
                            <form action="{{ route('loans.approve', $loan) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('¿Aprobar este préstamo?')" class="text-green-600 hover:underline">
                                    Aprobar
                                </button>
                            </form>
                            <form action="{{ route('loans.reject', $loan) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('¿Rechazar este préstamo?')" class="text-red-600 hover:underline">
                                    Rechazar
                                </button>
                            </form>
                            <a href="{{ route('loans.edit', $loan) }}" class="text-indigo-600 hover:underline">
                                Editar
                            </a>
                        @endif
                        <a href="{{ route('loans.show', $loan) }}" class="text-blue-600 hover:underline">
                            Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        No hay préstamos registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>

    </div>

</div>

@endsection
