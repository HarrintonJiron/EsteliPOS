@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Crear Proveedor</h1>
            <p class="text-sm text-gray-500">Datos fiscales y contacto</p>
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

    <form action="{{ route('proveedores.store') }}" method="POST" class="space-y-4">
        @csrf

        <div class="bg-white p-4 rounded-xl shadow space-y-4">
            <h2 class="text-lg font-semibold text-gray-700">Identificación</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-gray-600">Código</label>
                    <input name="code" value="{{ old('code') }}" placeholder="Ej: PR-001"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Nombre Comercial</label>
                    <input name="name" value="{{ old('name') }}" required placeholder="Ej: Agroquímicos del Norte"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Razón social (opcional)</label>
                    <input name="business_name" value="{{ old('business_name') }}" placeholder="Ej: Agroquímicos del Norte S.A."
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>

                <div>
                    <label class="text-sm text-gray-600">RUC</label>
                    <input name="ruc" value="{{ old('ruc') }}" placeholder="Ej: J0000000000000"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow space-y-4">
            <h2 class="text-lg font-semibold text-gray-700">Contacto</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Nombre del contacto</label>
                    <input name="contact_name" value="{{ old('contact_name') }}" placeholder="Ej: María López"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Teléfono</label>
                    <input name="phone" value="{{ old('phone') }}" placeholder="Ej: 8888-9999"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Email</label>
                    <input name="email" value="{{ old('email') }}" placeholder="correo@proveedor.com"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Ciudad</label>
                    <input name="city" value="{{ old('city') }}" placeholder="Ej: Matagalpa"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
                <div class="md:col-span-3">
                    <label class="text-sm text-gray-600">Dirección</label>
                    <input name="address" value="{{ old('address') }}" placeholder="Dirección completa"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow space-y-4">
            <h2 class="text-lg font-semibold text-gray-700">Condiciones</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-gray-600">Tipo</label>
                    <input name="type" value="{{ old('type') }}" placeholder="Ej: Fertilizantes"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Condición de pago</label>
                    <select name="payment_condition" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                        <option value="">—</option>
                        <option value="contado" {{ old('payment_condition') === 'contado' ? 'selected' : '' }}>Contado</option>
                        <option value="credito_15" {{ old('payment_condition') === 'credito_15' ? 'selected' : '' }}>Crédito 15 días</option>
                        <option value="credito_30" {{ old('payment_condition') === 'credito_30' ? 'selected' : '' }}>Crédito 30 días</option>
                        <option value="credito_60" {{ old('payment_condition') === 'credito_60' ? 'selected' : '' }}>Crédito 60 días</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Límite de crédito (C$)</label>
                    <input name="credit_limit" type="number" step="0.01" value="{{ old('credit_limit') }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Estado</label>
                    <select name="status" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('proveedores.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 inline-block">Cancelar</a>
            <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow">Guardar Proveedor</button>
        </div>
    </form>
</div>

@endsection
