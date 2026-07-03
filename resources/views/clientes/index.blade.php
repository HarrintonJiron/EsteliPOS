@extends('layouts.app')

@section('title', 'Clientes')

@section('content')

<div class="space-y-6">

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Clientes</h1>
            <p class="page-subtitle">Registro rápido con control de crédito y plazos de pago</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('creditos.index') }}" class="btn-outline text-sm">Ver Créditos</a>
            <button onclick="document.getElementById('modalCliente').classList.remove('hidden')" class="btn-primary">+ Cliente Rápido</button>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">Total Clientes</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['total'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-violet-500">
            <p class="text-xs text-slate-500">Con Crédito</p>
            <p class="text-2xl font-bold text-violet-600">{{ $stats['with_credit'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Cartera Total</p>
            <p class="text-lg font-bold text-amber-600">C$ {{ number_format($stats['portfolio']['balance_total'], 0) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-red-500">
            <p class="text-xs text-slate-500">Sobre Límite</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['over_limit'] }}</p>
        </div>
    </div>

    <form method="GET" class="card p-4">
        <div class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, teléfono o RUC..." class="input-field flex-1">
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
                    <th class="text-center">Plazo</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <p class="font-semibold text-slate-800">{{ $client->name }}</p>
                        @if($client->business_name)<p class="text-xs text-slate-500">{{ $client->business_name }}</p>@endif
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
                <tr><td colspan="7" class="text-center py-8 text-slate-500">No hay clientes registrados</td></tr>
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
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                <input type="text" name="name" required class="input-field" placeholder="Nombre del cliente" autofocus>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                <input type="text" name="phone" class="input-field" placeholder="8888-8888">
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="credit_enabled" value="1" id="creditToggle" class="rounded border-slate-300 text-indigo-600" onchange="document.getElementById('creditFields').classList.toggle('hidden', !this.checked)">
                <span class="text-sm font-medium text-slate-700">Habilitar crédito</span>
            </label>
            <div id="creditFields" class="hidden grid grid-cols-2 gap-3 p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Límite de crédito (C$)</label>
                    <input type="number" name="credit_limit" step="0.01" min="0" value="5000" class="input-field">
                    <p class="text-xs text-slate-400 mt-1">0 = sin límite</p>
                </div>
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Días para pagar</label>
                    <input type="number" name="credit_days" min="1" max="365" value="30" class="input-field">
                </div>
            </div>
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
