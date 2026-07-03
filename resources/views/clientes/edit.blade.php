@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Editar Cliente</h1>
            <p class="page-subtitle">{{ $client->name }}</p>
        </div>
        <a href="{{ route('clientes.show', $client->id) }}" class="btn-outline text-sm">Volver</a>
    </div>

    @if($creditSummary['balance'] > 0)
    <div class="card p-4 bg-amber-50 border border-amber-200 flex justify-between items-center">
        <div>
            <p class="text-sm text-amber-800">Saldo pendiente: <strong>C$ {{ number_format($creditSummary['balance'], 2) }}</strong></p>
            @if($creditSummary['over_limit'])<p class="text-xs text-red-600 font-medium">Sobre el límite de crédito</p>@endif
        </div>
        <a href="{{ route('creditos.show', $client->id) }}" class="btn-primary text-sm">Ver crédito</a>
    </div>
    @endif

    <form action="{{ route('clientes.update', $client->id) }}" method="POST" class="space-y-4">
        @csrf @method('PUT')

        <div class="card p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">Datos básicos</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nombre *</label>
                    <input name="name" value="{{ old('name', $client->name) }}" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Teléfono</label>
                    <input name="phone" value="{{ old('phone', $client->phone) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Código</label>
                    <input name="code" value="{{ old('code', $client->code) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email', $client->email) }}" class="input-field">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-600 mb-1">Razón social</label>
                    <input name="business_name" value="{{ old('business_name', $client->business_name) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">RUC</label>
                    <input name="ruc" value="{{ old('ruc', $client->ruc) }}" class="input-field">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-600 mb-1">Dirección</label>
                    <textarea name="address" rows="2" class="input-field">{{ old('address', $client->address) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card p-5 space-y-4 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Crédito</h2>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="credit_enabled" value="1" {{ old('credit_enabled', $client->credit_enabled) ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-sm">Habilitado</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Límite (C$)</label>
                    <input type="number" name="credit_limit" step="0.01" min="0" value="{{ old('credit_limit', $client->credit_limit) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Plazo (días)</label>
                    <input type="number" name="credit_days" min="1" max="365" value="{{ old('credit_days', $client->credit_days ?? 30) }}" class="input-field">
                </div>
                <div class="flex items-end">
                    <div class="p-3 bg-slate-50 rounded-xl w-full text-center">
                        <p class="text-xs text-slate-500">Disponible</p>
                        <p class="font-bold text-emerald-600">
                            @if($client->credit_enabled && $creditSummary['available_credit'] === null)
                                Ilimitado
                            @else
                                C$ {{ number_format($creditSummary['available_credit'] ?? 0, 2) }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>
@endsection
