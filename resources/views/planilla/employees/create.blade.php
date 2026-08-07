@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Registrar Nuevo Empleado
        </h1>

        <a href="{{ route('planilla.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-2 gap-6">
                <!-- Información Personal -->
                <div class="col-span-2">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Personal</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                    <input type="text" name="name" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cédula</label>
                    <input type="text" name="cedula"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cargo / Puesto *</label>
                    <input type="text" name="position" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="text" name="phone"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" name="address"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Información Laboral -->
                <div class="col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Laboral</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Salario Mensual (C$) *</label>
                    <input type="number" name="salary" required step="0.01" min="0"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tarifa por Hora (C$)</label>
                    <input type="number" name="hourly_rate" step="0.01" min="0"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Ingreso</label>
                    <input type="date" name="hire_date"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Contrato *</label>
                    <select name="contract_type" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="full_time">Tiempo Completo</option>
                        <option value="part_time">Medio Tiempo</option>
                        <option value="temporary">Temporal</option>
                        <option value="seasonal">Por Temporada</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Frecuencia de Pago *</label>
                    <select name="payment_frequency" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="monthly">Mensual</option>
                        <option value="biweekly">Quincenal</option>
                        <option value="weekly">Semanal</option>
                    </select>
                </div>

                <!-- Contacto de Emergencia -->
                <div class="col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Contacto de Emergencia</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de Contacto</label>
                    <input type="text" name="emergency_contact"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono de Emergencia</label>
                    <input type="text" name="emergency_phone"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Información Bancaria -->
                <div class="col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Información Bancaria</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Banco</label>
                    <input type="text" name="bank_name"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de Cuenta</label>
                    <input type="text" name="bank_account"
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
                    Guardar Empleado
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
