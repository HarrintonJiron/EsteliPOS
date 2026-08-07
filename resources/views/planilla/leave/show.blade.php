@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Detalles del Permiso
        </h1>

        <a href="{{ route('leave.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500">Empleado</p>
                <p class="font-medium text-lg">{{ $leaveRequest->employee?->name ?? 'Empleado no disponible' }}</p>
                <p class="text-sm text-gray-500">{{ $leaveRequest->employee?->position ?? '—' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Tipo de Permiso</p>
                <p class="font-medium text-lg">{{ $leaveRequest->type_label }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Fecha Inicio</p>
                <p class="font-medium">{{ $leaveRequest->start_date->format('d/m/Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Fecha Fin</p>
                <p class="font-medium">{{ $leaveRequest->end_date->format('d/m/Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Días Solicitados</p>
                <p class="font-medium text-lg">{{ $leaveRequest->days }} días</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Estado</p>
                <p class="font-medium">
                    <span class="px-3 py-1 rounded-full text-xs bg-{{ $leaveRequest->status_color }}-100 text-{{ $leaveRequest->status_color }}-700">
                        {{ $leaveRequest->status_label }}
                    </span>
                </p>
            </div>

            @if($leaveRequest->reason)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Razón</p>
                <p class="font-medium">{{ $leaveRequest->reason }}</p>
            </div>
            @endif

            @if($leaveRequest->rejection_reason)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Razón de Rechazo</p>
                <p class="font-medium text-red-600">{{ $leaveRequest->rejection_reason }}</p>
            </div>
            @endif

            @if($leaveRequest->approved_by && $leaveRequest->approvedBy)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Aprobado por</p>
                <p class="font-medium">{{ $leaveRequest->approvedBy->name }}</p>
                <p class="text-sm text-gray-500">{{ $leaveRequest->approved_at?->format('d/m/Y H:i') }}</p>
            </div>
            @endif
        </div>

        @if($leaveRequest->status === 'pending')
        <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
            <form action="{{ route('leave.approve', $leaveRequest) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('¿Aprobar este permiso?')" 
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Aprobar
                </button>
            </form>
            <a href="{{ route('leave.edit', $leaveRequest) }}"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Editar
            </a>
        </div>
        @endif
    </div>

</div>

@endsection
