@extends('layouts.app')

@section('title', 'Tipos de Cambio')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Tipos de Cambio</h1>
            <p class="page-subtitle">Configure las tasas de conversión entre monedas para el sistema.</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.edit'))
            <a href="{{ route('settings.exchange-rates.create') }}" class="btn-primary">+ Nuevo Tipo de Cambio</a>
            @endif
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>De</th>
                    <th>A</th>
                    <th class="text-right">Tasa</th>
                    <th>Fecha Vigencia</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rates as $rate)
                <tr>
                    <td class="font-mono font-semibold">{{ $rate->from_currency }}</td>
                    <td class="font-mono font-semibold">{{ $rate->to_currency }}</td>
                    <td class="text-right font-mono">{{ number_format($rate->rate, 6) }}</td>
                    <td>{{ $rate->effective_date->format('d/m/Y') }}</td>
                    <td>
                        @if($rate->is_active)
                            <span class="badge-success">Activo</span>
                        @else
                            <span class="badge-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-center whitespace-nowrap">
                        @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.edit'))
                        <a href="{{ route('settings.exchange-rates.edit', $rate) }}" class="text-indigo-600 hover:underline text-sm">Editar</a>
                        <form action="{{ route('settings.exchange-rates.destroy', $rate) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este tipo de cambio?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline text-sm ml-2">Eliminar</button>
                        </form>
                        @else
                            <span class="text-xs text-slate-400">Solo lectura</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-500 py-6">No hay tipos de cambio configurados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
