@extends('layouts.app')
@section('hide_back', true)

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Editar Deducción
        </h1>

        <a href="{{ route('deductions.index') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form action="{{ route('deductions.update', $deduction) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-6">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
                    <input type="text" disabled value="{{ $deduction->employee->name }} - {{ $deduction->employee->position }}"
                        class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Deducción *</label>
                    <select name="type" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('type') border-red-500 @enderror">
                        <option value="uniform" @selected(old('type', $deduction->type) === 'uniform')>Uniforme</option>
                        <option value="tools" @selected(old('type', $deduction->type) === 'tools')>Herramientas</option>
                        <option value="damages" @selected(old('type', $deduction->type) === 'damages')>Daños</option>
                        <option value="absence" @selected(old('type', $deduction->type) === 'absence')>Ausencia</option>
                        <option value="late" @selected(old('type', $deduction->type) === 'late')>Tardanza</option>
                        <option value="other" @selected(old('type', $deduction->type) === 'other')>Otro</option>
                    </select>
                    @error('type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Monto (C$) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required value="{{ old('amount', $deduction->amount) }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('amount') border-red-500 @enderror">
                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha *</label>
                    <input type="date" name="date" required value="{{ old('date', $deduction->date->format('Y-m-d')) }}"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Razón</label>
                    <textarea name="reason" rows="3"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('reason', $deduction->reason) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
                <a href="{{ route('deductions.index') }}"
                    class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
