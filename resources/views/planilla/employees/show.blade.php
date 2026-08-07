@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Detalles del Empleado: {{ $employee->name }}
        </h1>

        <div class="flex space-x-3">
            <a href="{{ route('employees.edit', $employee) }}"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                Editar
            </a>
            <a href="{{ route('planilla.index') }}"
                class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                Volver
            </a>
        </div>
    </div>

    <!-- Información Personal -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Personal</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Nombre</p>
                <p class="font-medium">{{ $employee->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Cédula</p>
                <p class="font-medium">{{ $employee->cedula ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Cargo</p>
                <p class="font-medium">{{ $employee->position }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Teléfono</p>
                <p class="font-medium">{{ $employee->phone ?? '-' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Dirección</p>
                <p class="font-medium">{{ $employee->address ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Información Laboral -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Laboral</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Salario Mensual</p>
                <p class="font-medium text-lg">C$ {{ number_format($employee->salary, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Tarifa por Hora</p>
                <p class="font-medium">{{ $employee->hourly_rate ? 'C$ ' . number_format($employee->hourly_rate, 2) : '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Fecha de Ingreso</p>
                <p class="font-medium">{{ $employee->hire_date ? $employee->hire_date->format('d/m/Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Años de Servicio</p>
                <p class="font-medium">{{ $employee->years_of_service }} años</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Tipo de Contrato</p>
                <p class="font-medium">{{ $employee->contract_type_label }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Frecuencia de Pago</p>
                <p class="font-medium">{{ $employee->payment_frequency_label }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Estado</p>
                <p class="font-medium">
                    @if($employee->is_active)
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">Activo</span>
                    @else
                        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs">Inactivo</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Vacaciones y Prestaciones -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Vacaciones</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Total Acumulado</span>
                    <span class="font-medium">{{ $vacationBalance['total_days'] }} días</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Días Usados</span>
                    <span class="font-medium">{{ $vacationBalance['used_days'] }} días</span>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <span class="text-sm font-medium text-gray-700">Disponibles</span>
                    <span class="font-bold text-green-600">{{ $vacationBalance['available_days'] }} días</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Prestaciones Laborales</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">13° Mes</span>
                    <span class="font-medium">C$ {{ number_format($benefits['thirteenth_month'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Pago Vacaciones</span>
                    <span class="font-medium">C$ {{ number_format($benefits['vacation_pay'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Indemnización</span>
                    <span class="font-medium">C$ {{ number_format($benefits['severance'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Permisos -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Historial de Permisos</h3>
        @forelse($leaveRequests as $leave)
            <div class="space-y-2">
                @foreach($leaveRequests as $leave)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium">{{ $leave->type_label }}</p>
                        <p class="text-sm text-gray-500">{{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }} ({{ $leave->days }} días)</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs bg-{{ $leave->status_color }}-100 text-{{ $leave->status_color }}-700">
                        {{ $leave->status_label }}
                    </span>
                </div>
                @endforeach
            </div>
        @empty
            <p class="text-gray-500 text-center py-4">No hay permisos registrados</p>
        @endforelse
    </div>

</div>

@endsection
