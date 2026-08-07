@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Editar Empleado: {{ $employee->name }}
        </h1>

        <a href="{{ route('planilla.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form action="{{ route('employees.update', $employee) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-2 gap-6">
                <!-- Información Personal -->
                <div class="col-span-2">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Personal</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                    <input type="text" name="name" value="{{ $employee->name }}" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cédula</label>
                    <input type="text" name="cedula" value="{{ $employee->cedula }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cargo / Puesto *</label>
                    <input type="text" name="position" value="{{ $employee->position }}" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="text" name="phone" value="{{ $employee->phone }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" name="address" value="{{ $employee->address }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Información Laboral -->
                <div class="col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Laboral</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Salario Mensual (C$) *</label>
                    <input type="number" name="salary" value="{{ $employee->salary }}" required step="0.01" min="0"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tarifa por Hora (C$)</label>
                    <input type="number" name="hourly_rate" value="{{ $employee->hourly_rate }}" step="0.01" min="0"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Ingreso</label>
                    <input type="date" name="hire_date" value="{{ $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '' }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Contrato *</label>
                    <select name="contract_type" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="full_time" {{ $employee->contract_type === 'full_time' ? 'selected' : '' }}>Tiempo Completo</option>
                        <option value="part_time" {{ $employee->contract_type === 'part_time' ? 'selected' : '' }}>Medio Tiempo</option>
                        <option value="temporary" {{ $employee->contract_type === 'temporary' ? 'selected' : '' }}>Temporal</option>
                        <option value="seasonal" {{ $employee->contract_type === 'seasonal' ? 'selected' : '' }}>Por Temporada</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Frecuencia de Pago *</label>
                    <select name="payment_frequency" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="monthly" {{ $employee->payment_frequency === 'monthly' ? 'selected' : '' }}>Mensual</option>
                        <option value="biweekly" {{ $employee->payment_frequency === 'biweekly' ? 'selected' : '' }}>Quincenal</option>
                        <option value="weekly" {{ $employee->payment_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="is_active"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="1" {{ $employee->is_active ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ !$employee->is_active ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <!-- Contacto de Emergencia -->
                <div class="col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Contacto de Emergencia</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de Contacto</label>
                    <input type="text" name="emergency_contact" value="{{ $employee->emergency_contact }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono de Emergencia</label>
                    <input type="text" name="emergency_phone" value="{{ $employee->emergency_phone }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Información Bancaria -->
                <div class="col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Bancaria</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Banco</label>
                    <input type="text" name="bank_name" value="{{ $employee->bank_name }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de Cuenta</label>
                    <input type="text" name="bank_account" value="{{ $employee->bank_account }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
                <a href="{{ route('planilla.index') }}"
                    class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Actualizar Empleado
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
