@extends('layouts.app')
@section('hide_back', true)

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

    @if($errors->any())
    <div class="card p-4 bg-red-50 border border-red-200 text-red-800 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

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
                    <label class="block text-sm text-slate-600 mb-1">Tipo de cliente *</label>
                    <select name="client_type" id="clientType" class="select-field" required onchange="toggleClientTaxFields()">
                        <option value="natural" {{ old('client_type', $client->client_type ?? 'natural') === 'natural' ? 'selected' : '' }}>Persona Natural</option>
                        <option value="company" {{ old('client_type', $client->client_type) === 'company' ? 'selected' : '' }}>Empresa</option>
                    </select>
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
                <div class="md:col-span-2" id="businessNameField">
                    <label class="block text-sm text-slate-600 mb-1">Razón social</label>
                    <input name="business_name" value="{{ old('business_name', $client->business_name) }}" class="input-field">
                </div>
                <div id="cedulaField">
                    <label class="block text-sm text-slate-600 mb-1">Cédula</label>
                    <input name="cedula" value="{{ old('cedula', $client->cedula) }}" class="input-field">
                </div>
                <div id="rucField">
                    <label class="block text-sm text-slate-600 mb-1">RUC</label>
                    <input name="ruc" value="{{ old('ruc', $client->ruc) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Tipo de contribuyente</label>
                    <input name="taxpayer_type" value="{{ old('taxpayer_type', $client->taxpayer_type) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Departamento</label>
                    <input name="department" value="{{ old('department', $client->department) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Municipio</label>
                    <input name="municipality" value="{{ old('municipality', $client->municipality) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Estado</label>
                    <select name="status" class="select-field">
                        <option value="active" {{ old('status', $client->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status', $client->status) === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-600 mb-1">Dirección</label>
                    <textarea name="address" rows="2" class="input-field">{{ old('address', $client->address) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">Lista de precios</h2>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Precios en POS / facturación</label>
                <select name="price_list_id" class="select-field">
                    <option value="">Lista general (default)</option>
                    @foreach($priceLists ?? [] as $list)
                        <option value="{{ $list->id }}" @selected(old('price_list_id', $client->price_list_id ?? '') == $list->id)>{{ $list->name }} ({{ $list->code }})</option>
                    @endforeach
                </select>
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

        {{-- MORA --}}
        <div class="card p-5 space-y-4 border-l-4 border-red-400">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-800">Cargo por Mora</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Se calcula sobre facturas vencidas y se muestra en el estado de cuenta.</p>
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="mora_enabled" value="1" {{ old('mora_enabled', $client->mora_enabled) ? 'checked' : '' }} class="rounded text-red-500">
                    <span class="text-sm">Habilitar mora</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">% mora por día vencido</label>
                    <input type="number" name="mora_rate" step="0.01" min="0" max="100"
                           value="{{ old('mora_rate', $client->mora_rate ?? 0.5) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">Ej: 0.5 = 0.5% diario sobre el principal</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Días de gracia</label>
                    <input type="number" name="mora_grace_days" min="0" max="90"
                           value="{{ old('mora_grace_days', $client->mora_grace_days ?? 0) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">Días extra antes de aplicar mora</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Mora máxima (% del principal)</label>
                    <input type="number" name="mora_max_pct" step="0.01" min="0" max="100"
                           value="{{ old('mora_max_pct', $client->mora_max_pct ?? 30) }}" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">0 = sin tope de mora</p>
                </div>
            </div>
            @if($client->mora_enabled && ($creditSummary['mora'] ?? 0) > 0)
            <div class="bg-red-50 rounded-xl p-3 flex items-center justify-between">
                <p class="text-sm text-red-700 font-medium">Mora acumulada actual</p>
                <p class="text-lg font-bold text-red-700">C$ {{ number_format($creditSummary['mora'] ?? 0, 2) }}</p>
            </div>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleClientTaxFields() {
    const type = document.getElementById('clientType')?.value;
    const business = document.getElementById('businessNameField');
    const ruc = document.getElementById('rucField');
    const cedula = document.getElementById('cedulaField');
    if (!business || !ruc || !cedula) return;

    const isCompany = type === 'company';
    business.classList.toggle('hidden', !isCompany);
    ruc.classList.toggle('hidden', !isCompany);
    cedula.classList.toggle('hidden', isCompany);
}

document.addEventListener('DOMContentLoaded', toggleClientTaxFields);
</script>
@endpush
