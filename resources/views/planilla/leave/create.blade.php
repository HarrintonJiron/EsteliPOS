@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Nueva Solicitud de Permiso
        </h1>

        <a href="{{ route('leave.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form action="{{ route('leave.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empleado *</label>
                    <select name="employee_id" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seleccione un empleado</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->position }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Permiso *</label>
                    <select name="type" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seleccione tipo</option>
                        <option value="vacation">Vacaciones</option>
                        <option value="sick">Enfermedad</option>
                        <option value="personal">Personal</option>
                        <option value="maternity">Maternidad</option>
                        <option value="paternity">Paternidad</option>
                        <option value="bereavement">Duelo</option>
                        <option value="unpaid">Sin Goce</option>
                    </select>
                </div>

                <div></div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio *</label>
                    <input type="date" name="start_date" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin *</label>
                    <input type="date" name="end_date" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Razón</label>
                    <textarea name="reason" rows="3"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Describa el motivo del permiso (opcional)"></textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
                <a href="{{ route('leave.index') }}"
                    class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Crear Solicitud
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
