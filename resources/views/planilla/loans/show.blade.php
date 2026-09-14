@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Detalles del Préstamo
        </h1>

        <a href="{{ route('loans.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <p class="text-sm text-gray-500">Empleado</p>
                <p class="font-medium text-lg">{{ $loan->employee?->name ?? 'Empleado no disponible' }}</p>
                <p class="text-sm text-gray-500">{{ $loan->employee?->position ?? '—' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Tipo</p>
                <p class="font-medium text-lg">{{ $loan->type_label }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Monto Total</p>
                <p class="font-medium text-lg">C$ {{ number_format($loan->amount, 2) }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Cuota Mensual</p>
                <p class="font-medium text-lg">C$ {{ number_format($loan->monthly_payment, 2) }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Meses</p>
                <p class="font-medium">{{ $loan->months }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Saldo Restante</p>
                <p class="font-medium text-lg text-red-600">C$ {{ number_format($loan->remaining_balance, 2) }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Fecha Inicio</p>
                <p class="font-medium">{{ $loan->start_date->format('d/m/Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Fecha Fin</p>
                <p class="font-medium">{{ $loan->end_date->format('d/m/Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Estado</p>
                <p class="font-medium">
                    <span class="px-3 py-1 rounded-full text-xs bg-{{ $loan->status_color }}-100 text-{{ $loan->status_color }}-700">
                        {{ $loan->status_label }}
                    </span>
                </p>
            </div>

            @if($loan->reason)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Razón</p>
                <p class="font-medium">{{ $loan->reason }}</p>
            </div>
            @endif

            @if($loan->approvedBy)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Aprobado por</p>
                <p class="font-medium">{{ $loan->approvedBy->name }}</p>
                <p class="text-sm text-gray-500">{{ $loan->approved_at?->format('d/m/Y H:i') }}</p>
            </div>
            @endif
        </div>

        @if($loan->status === 'pending')
        <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
            <form action="{{ route('loans.approve', $loan) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('¿Aprobar este préstamo?')"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Aprobar
                </button>
            </form>
            <form action="{{ route('loans.reject', $loan) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('¿Rechazar este préstamo?')"
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Rechazar
                </button>
            </form>
            <a href="{{ route('loans.edit', $loan) }}"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Editar
            </a>
        </div>
        @endif
    </div>

</div>

@endsection
