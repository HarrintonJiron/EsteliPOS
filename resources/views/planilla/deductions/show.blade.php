@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Detalles de la Deducción
        </h1>

        <a href="{{ route('deductions.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <p class="text-sm text-gray-500">Empleado</p>
                <p class="font-medium text-lg">{{ $deduction->employee->name }}</p>
                <p class="text-sm text-gray-500">{{ $deduction->employee->position }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Tipo de Deducción</p>
                <p class="font-medium text-lg">{{ $deduction->type_label }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Monto</p>
                <p class="font-medium text-lg text-red-600">C$ {{ number_format($deduction->amount, 2) }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Fecha</p>
                <p class="font-medium">{{ $deduction->date->format('d/m/Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Estado</p>
                <p class="font-medium">
                    <span class="px-3 py-1 rounded-full text-xs bg-{{ $deduction->status_color }}-100 text-{{ $deduction->status_color }}-700">
                        {{ $deduction->status_label }}
                    </span>
                </p>
            </div>

            @if($deduction->reason)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Razón</p>
                <p class="font-medium">{{ $deduction->reason }}</p>
            </div>
            @endif

            @if($deduction->approvedBy)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Aprobado por</p>
                <p class="font-medium">{{ $deduction->approvedBy->name }}</p>
                <p class="text-sm text-gray-500">{{ $deduction->approved_at?->format('d/m/Y H:i') }}</p>
            </div>
            @endif
        </div>

        <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
            @if($deduction->status === 'pending')
                <form action="{{ route('deductions.approve', $deduction) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Aprobar esta deducción?')"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Aprobar
                    </button>
                </form>
                <a href="{{ route('deductions.edit', $deduction) }}"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Editar
                </a>
            @endif
            @if($deduction->status === 'approved')
                <form action="{{ route('deductions.mark-deducted', $deduction) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Marcar como deducida?')"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Marcar Deducida
                    </button>
                </form>
            @endif
        </div>
    </div>

</div>

@endsection
