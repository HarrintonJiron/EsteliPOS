@extends('layouts.app')

@section('title', 'Detalle de Ajuste #' . $adjustment->id)

@section('content')

<div class="max-w-4xl mx-auto space-y-4">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Ajuste de Inventario #{{ $adjustment->id }}</h1>
            <p class="text-sm text-gray-500">Registrado el {{ $adjustment->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="flex space-x-2">
            <a href="{{ route('ajustes.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Volver</a>
        </div>
    </div>

    {{-- Información del Ajuste --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500">Tipo de Ajuste</p>
                <span class="inline-block px-4 py-2 rounded-full text-sm font-bold mt-2
                    {{ $adjustment->type === 'increase' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $adjustment->type === 'decrease' ? 'bg-red-100 text-red-700' : '' }}
                    {{ $adjustment->type === 'count' ? 'bg-blue-100 text-blue-700' : '' }}">
                    {{ $adjustment->type_label }}
                </span>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500">Cantidad Ajustada</p>
                <p class="text-3xl font-bold {{ $adjustment->quantity >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                    {{ $adjustment->quantity >= 0 ? '+' : '' }}{{ $adjustment->quantity }}
                </p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500">Usuario</p>
                <p class="text-lg font-semibold mt-2">{{ $adjustment->user->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Detalles del Stock --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Cambio de Stock</h2>

        <div class="flex items-center justify-center space-x-8">
            <div class="text-center">
                <p class="text-sm text-gray-500 mb-2">Stock Anterior</p>
                <p class="text-4xl font-bold text-gray-600">{{ $adjustment->stock_before }}</p>
            </div>

            <div class="text-4xl text-gray-400">→</div>

            <div class="text-center">
                <p class="text-sm text-gray-500 mb-2">Nuevo Stock</p>
                <p class="text-4xl font-bold text-green-600">{{ $adjustment->stock_after }}</p>
            </div>
        </div>
    </div>

    {{-- Información del Producto --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Producto Afectado</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500">Nombre</p>
                <p class="font-semibold">{{ $adjustment->product->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Código</p>
                <p class="font-semibold">{{ $adjustment->product->code ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Lote</p>
                <p class="font-semibold">{{ $adjustment->product->lot ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Ubicación</p>
                <p class="font-semibold">{{ $adjustment->product->location ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Motivo y Referencia --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Detalles del Ajuste</h2>

        <div class="space-y-4">
            <div>
                <p class="text-sm text-gray-500">Motivo</p>
                <p class="font-medium text-gray-800">{{ $adjustment->reason }}</p>
            </div>

            @if($adjustment->reference)
                <div>
                    <p class="text-sm text-gray-500">Referencia</p>
                    <p class="font-medium text-gray-800">{{ $adjustment->reference }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Acciones --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-500">
                Este ajuste fue registrado el {{ $adjustment->created_at->format('d/m/Y') }} a las {{ $adjustment->created_at->format('H:i') }}
            </p>

            <form action="{{ route('ajustes.destroy', $adjustment->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este ajuste? El stock se restaurará al valor anterior.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    Eliminar Ajuste
                </button>
            </form>
        </div>
    </div>

</div>

@endsection
