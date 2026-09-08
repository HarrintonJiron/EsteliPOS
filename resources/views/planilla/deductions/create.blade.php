@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Nueva Deducción
        </h1>

        <a href="{{ route('deductions.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form action="{{ route('deductions.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empleado *</label>
                    <select name="employee_id" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee_id') border-red-500 @enderror">
                        <option value="">Seleccione un empleado</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                                {{ $employee->name }} - {{ $employee->position }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Deducción *</label>
                    <select name="type" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('type') border-red-500 @enderror">
                        <option value="">Seleccione tipo</option>
                        <option value="uniform" @selected(old('type') === 'uniform')>Uniforme</option>
                        <option value="tools" @selected(old('type') === 'tools')>Herramientas</option>
                        <option value="damages" @selected(old('type') === 'damages')>Daños</option>
                        <option value="absence" @selected(old('type') === 'absence')>Ausencia</option>
                        <option value="late" @selected(old('type') === 'late')>Tardanza</option>
                        <option value="other" @selected(old('type') === 'other')>Otro</option>
                    </select>
                    @error('type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Monto (C$) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required value="{{ old('amount') }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('amount') border-red-500 @enderror">
                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha *</label>
                    <input type="date" name="date" required value="{{ old('date') }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Razón</label>
                    <textarea name="reason" rows="3"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Describa el motivo de la deducción (opcional)">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
                <a href="{{ route('deductions.index') }}"
                    class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Crear Deducción
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
