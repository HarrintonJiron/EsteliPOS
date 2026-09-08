@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Detalles del Bono
        </h1>

        <a href="{{ route('bonuses.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <p class="text-sm text-gray-500">Empleado</p>
                <p class="font-medium text-lg">{{ $bonus->employee->name }}</p>
                <p class="text-sm text-gray-500">{{ $bonus->employee->position }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Tipo de Bono</p>
                <p class="font-medium text-lg">{{ $bonus->type_label }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Monto</p>
                <p class="font-medium text-lg text-green-700">C$ {{ number_format($bonus->amount, 2) }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Fecha</p>
                <p class="font-medium">{{ $bonus->date->format('d/m/Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Estado</p>
                <p class="font-medium">
                    <span class="px-3 py-1 rounded-full text-xs bg-{{ $bonus->status_color }}-100 text-{{ $bonus->status_color }}-700">
                        {{ $bonus->status_label }}
                    </span>
                </p>
            </div>

            @if($bonus->reason)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Razón</p>
                <p class="font-medium">{{ $bonus->reason }}</p>
            </div>
            @endif

            @if($bonus->approvedBy)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Aprobado por</p>
                <p class="font-medium">{{ $bonus->approvedBy->name }}</p>
                <p class="text-sm text-gray-500">{{ $bonus->approved_at?->format('d/m/Y H:i') }}</p>
            </div>
            @endif
        </div>

        <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
            @if($bonus->status === 'pending')
                <form action="{{ route('bonuses.approve', $bonus) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Aprobar este bono?')"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Aprobar
                    </button>
                </form>
                <a href="{{ route('bonuses.edit', $bonus) }}"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Editar
                </a>
            @endif
            @if($bonus->status === 'approved')
                <form action="{{ route('bonuses.mark-paid', $bonus) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Marcar como pagado?')"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Marcar Pagado
                    </button>
                </form>
            @endif
        </div>
    </div>

</div>

@endsection
