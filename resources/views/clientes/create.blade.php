@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Crear Cliente</h1>
            <p class="text-sm text-gray-500">Datos para facturación y contacto</p>
        </div>
        <span class="bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm">Nuevo</span>
    </div>

    @if($errors->any())
        <div class="bg-red-100 text-red-800 px-4 py-3 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('clientes.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white p-6 rounded-xl shadow space-y-6">
            <h2 class="text-lg font-semibold text-gray-700">Identificación</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-sm text-gray-600">Código (interno)</label>
                    <input name="code" value="{{ old('code') }}" placeholder="Ej: CL-00023"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white"/>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Nombre / Contacto</label>
                    <input name="name" value="{{ old('name') }}" required placeholder="Ej: Juan Pérez"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white"/>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Razón social (si aplica)</label>
                    <input name="business_name" value="{{ old('business_name') }}" placeholder="Ej: Cooperativa San José R.L."
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white"/>
                </div>

                <div>
                    <label class="text-sm text-gray-600">RUC</label>
                    <input name="ruc" value="{{ old('ruc') }}" placeholder="001-123456-0000A"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white"/>
                    <p class="text-xs text-gray-400 mt-1">Formato sugerido: 001-123456-0000A</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow space-y-6">
            <h2 class="text-lg font-semibold text-gray-700">Contacto</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-sm text-gray-600">Teléfono</label>
                    <input name="phone" value="{{ old('phone') }}" placeholder="Ej: 8888-9999"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white"/>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Email</label>
                    <input name="email" value="{{ old('email') }}" placeholder="correo@cliente.com"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white"/>
                </div>

                <div class="md:col-span-3">
                    <label class="text-sm text-gray-600">Dirección</label>
                    <textarea name="address" rows="3" placeholder="Municipio, departamento, referencias..."
                              class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">{{ old('address') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('clientes.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 inline-block">
                Cancelar
            </a>
            <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow inline-block">
                Guardar Cliente
            </button>
        </div>
    </form>
</div>

@endsection
