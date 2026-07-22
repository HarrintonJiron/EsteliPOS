@extends('layouts.app')

@section('title', 'Proformas / Cotizaciones')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Proformas / Cotizaciones</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gestión de cotizaciones y presupuestos</p>
        </div>
        <a href="{{ route('proformas.pos') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Proforma
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
        <form method="GET" class="flex gap-3 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar por número o cliente..."
                class="flex-1 min-w-48 px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
            <select name="status" class="px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                <option value="">Todos los estados</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Enviada</option>
                <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Aceptada</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazada</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expirada</option>
            </select>
            <input type="date" name="date" value="{{ request('date') }}"
                class="px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl hover:bg-slate-700">Filtrar</button>
            @if(request()->hasAny(['search','status','date']))
                <a href="{{ route('proformas.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm rounded-xl hover:bg-slate-200">Limpiar</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">#</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Cliente</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Vence</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Total</th>
                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Estado</th>
                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($proformas as $proforma)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-mono font-semibold text-indigo-700">{{ $proforma->proforma_number }}</td>
                    <td class="px-4 py-3 text-slate-800">{{ $proforma->client_name ?? 'Cliente General' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $proforma->date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        @if($proforma->expiry_date)
                            <span class="{{ $proforma->expiry_date->isPast() ? 'text-red-600 font-semibold' : '' }}">
                                {{ $proforma->expiry_date->format('d/m/Y') }}
                            </span>
                        @else —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-slate-900">C$ {{ number_format($proforma->total, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $proforma->statusColor() }}">
                            {{ $proforma->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('proformas.show', $proforma->id) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Ver">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('proformas.pdf', $proforma->id) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition" title="PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </a>
                            <a href="{{ route('proformas.ticket', $proforma->id) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Ticket">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </a>
                            <form action="{{ route('proformas.destroy', $proforma->id) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar esta proforma?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p>No hay proformas registradas</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($proformas->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $proformas->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
