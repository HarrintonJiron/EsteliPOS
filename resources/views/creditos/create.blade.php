@extends('layouts.app')

@section('title', 'Registrar Abono - ' . $client->name)

@section('content')

<div class="max-w-2xl mx-auto px-4 py-8">

    {{-- Encabezado --}}
    <div class="mb-8">
        <a href="{{ route('creditos.show', $client->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold mb-4 inline-block">
            ← Volver
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Registrar Abono</h1>
        <p class="text-gray-600">{{ $client->name }}</p>
    </div>

    {{-- Resumen de Deuda --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-50 rounded shadow p-6 border-l-4 border-blue-900">
            <p class="text-gray-600 text-sm mb-1">Deuda Total</p>
            <p class="text-2xl font-bold text-blue-900">C$ {{ number_format($totalDebt, 2) }}</p>
        </div>
        <div class="bg-green-50 rounded shadow p-6 border-l-4 border-green-700">
            <p class="text-gray-600 text-sm mb-1">Abonos Anteriores</p>
            <p class="text-2xl font-bold text-green-700">C$ {{ number_format($totalDebt - $balance, 2) }}</p>
        </div>
        <div class="bg-red-50 rounded shadow p-6 border-l-4 border-red-700">
            <p class="text-gray-600 text-sm mb-1">Saldo Pendiente</p>
            <p class="text-2xl font-bold text-red-700">C$ {{ number_format($balance, 2) }}</p>
        </div>
    </div>

    {{-- Formulario --}}
    <form action="{{ route('creditos.store') }}" method="POST" class="bg-white rounded shadow p-8">
        @csrf

        <input type="hidden" name="client_id" value="{{ $client->id }}">

        {{-- Monto del abono --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Monto del Abono *
            </label>
            <div class="relative">
                <span class="absolute left-4 top-3 text-gray-600 font-semibold">C$</span>
                <input 
                    type="number" 
                    name="amount" 
                    step="0.01"
                    min="0.01"
                    max="{{ $balance }}"
                    placeholder="0.00"
                    required
                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded focus:border-blue-900 focus:outline-none"
                    @error('amount') style="border-color: rgb(185, 28, 28);" @enderror>
            </div>
            @error('amount')
                <p class="text-red-700 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-600 mt-1">Máximo disponible: C$ {{ number_format($balance, 2) }}</p>
        </div>

        {{-- Tipo de pago --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Tipo de Pago *
            </label>
            <select 
                name="payment_type" 
                required
                class="w-full px-4 py-3 border-2 border-gray-300 rounded focus:border-blue-900 focus:outline-none"
                @error('payment_type') style="border-color: rgb(185, 28, 28);" @enderror>
                <option value="">-- Seleccionar --</option>
                <option value="cash">💰 Efectivo</option>
                <option value="transfer">🏦 Transferencia / Tarjeta</option>
                <option value="check">📝 Cheque</option>
                <option value="other">📋 Otro</option>
            </select>
            @error('payment_type')
                <p class="text-red-700 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Número de referencia --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Número de Referencia (opcional)
            </label>
            <input 
                type="text" 
                name="reference_number" 
                placeholder="Ej: 123456, número de transferencia, etc."
                class="w-full px-4 py-3 border-2 border-gray-300 rounded focus:border-blue-900 focus:outline-none"
                @error('reference_number') style="border-color: rgb(185, 28, 28);" @enderror>
            @error('reference_number')
                <p class="text-red-700 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Notas --}}
        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Notas (opcional)
            </label>
            <textarea 
                name="notes" 
                rows="3"
                placeholder="Agregar notas adicionales sobre el abono..."
                class="w-full px-4 py-3 border-2 border-gray-300 rounded focus:border-blue-900 focus:outline-none resize-none"
                @error('notes') style="border-color: rgb(185, 28, 28);" @enderror></textarea>
            @error('notes')
                <p class="text-red-700 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Botones --}}
        <div class="flex gap-4">
            <button 
                type="submit"
                class="flex-1 bg-green-700 hover:bg-green-800 text-white font-bold py-3 rounded transition-colors">
                ✓ Guardar Abono
            </button>
            <a 
                href="{{ route('creditos.show', $client->id) }}"
                class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-bold py-3 rounded transition-colors text-center">
                Cancelar
            </a>
        </div>
    </form>

</div>

@endsection
