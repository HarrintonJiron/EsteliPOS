@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Nuevo Préstamo / Anticipo
        </h1>

        <a href="{{ route('loans.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form action="{{ route('loans.store') }}" method="POST">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                    <select name="type" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('type') border-red-500 @enderror">
                        <option value="">Seleccione tipo</option>
                        <option value="loan" @selected(old('type') === 'loan')>Préstamo</option>
                        <option value="advance" @selected(old('type') === 'advance')>Anticipo</option>
                        <option value="deduction" @selected(old('type') === 'deduction')>Deducción</option>
                    </select>
                    @error('type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Monto Total (C$) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required value="{{ old('amount') }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('amount') border-red-500 @enderror">
                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meses de Pago *</label>
                    <input type="number" name="months" min="1" required value="{{ old('months', 1) }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('months') border-red-500 @enderror">
                    @error('months')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio *</label>
                    <input type="date" name="start_date" required value="{{ old('start_date') }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('start_date') border-red-500 @enderror">
                    @error('start_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Razón</label>
                    <textarea name="reason" rows="3"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Describa el motivo del préstamo (opcional)">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
                <a href="{{ route('loans.index') }}"
                    class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Crear Préstamo
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
