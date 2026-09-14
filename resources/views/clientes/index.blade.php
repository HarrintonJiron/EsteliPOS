@extends('layouts.app')

@section('title', 'Clientes')

@section('content')

<div class="ex-shell">

    <x-ui.command-hero
        kicker="Cartera"
        title="Clientes"
        subtitle="Registro tributario y control de crédito"
        metric-label="Cartera total"
        :metric-value="'C$ ' . number_format($stats['portfolio']['balance_total'], 0)"
        :meta="[$stats['total'] . ' clientes', $stats['with_credit'] . ' con crédito']"
        :stats="[
            ['label' => 'Clientes', 'value' => number_format($stats['total'])],
            ['label' => 'Con crédito', 'value' => number_format($stats['with_credit'])],
            ['label' => 'Sobre límite', 'value' => number_format($stats['over_limit'])],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('creditos.index') }}" class="ex-btn">Ver créditos</a>
            <button type="button" onclick="document.getElementById('modalCliente').classList.remove('hidden')" class="ex-btn ex-btn--solid">+ Cliente rápido</button>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="ex-kpis ex-kpis--4">
        <x-ui.command-kpi label="Total clientes" :value="number_format($stats['total'])" />
        <x-ui.command-kpi label="Con crédito" :value="number_format($stats['with_credit'])" />
        <x-ui.command-kpi label="Cartera" :value="'C$ ' . number_format($stats['portfolio']['balance_total'], 0)" />
        <x-ui.command-kpi label="Sobre límite" :value="number_format($stats['over_limit'])" />
    </div>

    <form method="GET" class="card p-4">
        <div class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, razón social, cédula o RUC..." class="input-field flex-1">
            <button type="submit" class="btn-primary">Buscar</button>
        </div>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th class="text-right">Límite</th>
                    <th class="text-right">Saldo</th>
                    <th class="text-right">Disponible</th>
                    <th class="text-center">Plazo</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <p class="font-semibold text-slate-800">{{ $client->legal_name ?? $client->name }}</p>
                        <p class="text-xs text-slate-500">{{ ($client->is_company ?? false) ? 'Empresa' : 'Persona Natural' }} · {{ $client->document_label }}: {{ $client->document_number ?? '—' }}</p>
                    </td>
                    <td>{{ $client->phone ?? '—' }}</td>
                    <td class="text-right">
                        @if($client->credit_enabled)
                            {{ (float)$client->credit_limit > 0 ? 'C$ '.number_format($client->credit_limit, 2) : 'Ilimitado' }}
                        @else
                            <span class="text-slate-400">Contado</span>
                        @endif
                    </td>
                    <td class="text-right font-semibold {{ ($client->balance ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        C$ {{ number_format($client->balance ?? 0, 2) }}
                    </td>
                    <td class="text-right font-semibold text-indigo-700">
                        @if(!$client->credit_enabled)
                            —
                        @elseif($client->available_credit === null)
                            Ilimitado
                        @else
                            C$ {{ number_format((float) $client->available_credit, 2) }}
                        @endif
                    </td>
                    <td class="text-center text-sm text-slate-600">
                        {{ $client->credit_enabled ? ($client->credit_days ?? 30).' días' : '—' }}
                    </td>
                    <td>
                        @if($client->over_limit ?? false)
                            <span class="badge-danger">Sobre límite</span>
                        @elseif(($client->balance ?? 0) > 0)
                            <span class="badge-warning">Con deuda</span>
                        @elseif($client->credit_enabled)
                            <span class="badge-info">Crédito activo</span>
                        @else
                            <span class="badge-success">Contado</span>
                        @endif
                    </td>
                    <td class="text-center space-x-2">
                        <a href="{{ route('clientes.show', $client->id) }}" class="text-indigo-600 text-sm font-medium">Ver</a>
                        <a href="{{ route('clientes.edit', $client->id) }}" class="text-slate-500 text-sm">Editar</a>
                        @if($client->credit_enabled && ($client->balance ?? 0) > 0)
                        <a href="{{ route('creditos.show', $client->id) }}" class="text-emerald-600 text-sm">Crédito</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-8 text-slate-500">No hay clientes registrados</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">{{ $clients->links() }}</div>
    </div>
</div>

{{-- Modal registro rápido --}}
<div id="modalCliente" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="p-5 border-b border-slate-200 flex justify-between items-center">
            <h2 class="font-bold text-slate-800">Cliente Rápido</h2>
            <button type="button" onclick="document.getElementById('modalCliente').classList.add('hidden')" class="text-slate-400 text-xl">×</button>
        </div>
        <form action="{{ route('clientes.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="quick_client_form" value="1">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="input-field" placeholder="Nombre del cliente" autofocus>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de cliente *</label>
                <select name="client_type" id="quickClientType" class="select-field" required>
                    <option value="natural" @selected(old('client_type', 'natural') === 'natural')>Persona Natural</option>
                    <option value="company" @selected(old('client_type') === 'company')>Empresa</option>
                </select>
                @error('client_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono *</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required class="input-field" placeholder="8888-8888">
                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    name="save_cedula_identity"
                    value="1"
                    id="quickSaveCedula"
                    class="rounded border-slate-300 text-indigo-600"
                    onchange="toggleQuickCedulaField()"
                    @checked(old('save_cedula_identity'))
                >
                <span class="text-sm font-medium text-slate-700">Guardar cédula de identidad</span>
            </label>

            <div id="quickCedulaField" class="hidden">
                <label class="block text-sm font-medium text-slate-700 mb-1">Cédula</label>
                <input type="text" name="cedula" value="{{ old('cedula') }}" class="input-field" placeholder="001-123456-0000A">
                @error('cedula')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <input type="hidden" name="status" value="active">
            <div class="flex justify-between items-center pt-2">
                <a href="{{ route('clientes.create') }}" class="text-sm text-indigo-600">Formulario completo (Pro) →</a>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('modalCliente').classList.add('hidden')" class="btn-outline text-sm">Cancelar</button>
                    <button type="submit" class="btn-primary text-sm">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleQuickCedulaField() {
    const enabled = document.getElementById('quickSaveCedula')?.checked;
    const cedula = document.getElementById('quickCedulaField');
    if (!cedula) return;
    cedula.classList.toggle('hidden', !enabled);
}
document.addEventListener('DOMContentLoaded', toggleQuickCedulaField);

document.addEventListener('DOMContentLoaded', function () {
    const hasQuickErrors = {{ old('quick_client_form') ? 'true' : 'false' }};
    if (hasQuickErrors) {
        document.getElementById('modalCliente')?.classList.remove('hidden');
    }
});
</script>
@endpush
