@extends('layouts.app')

@section('title', 'Períodos Fiscales')

@section('content')

@php
    $canClose = auth()->user()?->isAdmin() || auth()->user()?->hasPermission('contabilidad.close_period');
@endphp

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Contabilidad · Períodos Fiscales</h1>
            <p class="page-subtitle">Cierre mensual y anual — los períodos cerrados bloquean nuevos movimientos</p>
        </div>
    </div>

    <form method="GET" class="card p-4 flex items-center gap-3">
        <label class="text-sm font-medium text-slate-700">Año</label>
        <select name="year" class="select-field w-32" onchange="this.form.submit()">
            @foreach(range(now()->year, now()->year - 4) as $y)
                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>

    <div class="card p-4 flex items-center justify-between {{ $annual->status === 'closed' ? 'border-l-4 border-red-500' : 'border-l-4 border-emerald-500' }}">
        <div>
            <p class="font-semibold text-slate-800">Cierre Anual {{ $year }}</p>
            @if($annual->status === 'closed')
                <p class="text-xs text-slate-500">Cerrado el {{ $annual->closed_at?->format('d/m/Y H:i') }} por {{ $annual->closedBy?->name }}</p>
                @if($closingEntry)
                    <a class="text-xs text-indigo-600 hover:underline" href="{{ route('contabilidad.asientos.show', $closingEntry) }}">Asiento de cierre {{ $closingEntry->number }} · {{ $closingEntry->status_label }}</a>
                @endif
            @else
                <p class="text-xs text-slate-500">Requiere los 12 meses cerrados y ningún asiento pendiente</p>
            @endif
        </div>
        @if($canClose)
            @if($annual->status === 'closed')
                <form action="{{ route('contabilidad.periodos.reabrir-anio', $annual) }}" method="POST" onsubmit="return confirm('¿Reabrir el año {{ $year }}?')">
                    @csrf
                    <input type="text" name="notes" maxlength="1000" placeholder="Motivo de reapertura" class="input-field mb-2" required>
                    <button type="submit" class="btn-outline">Reabrir Año</button>
                </form>
            @else
                <form action="{{ route('contabilidad.periodos.cerrar-anio', $annual) }}" method="POST" onsubmit="return confirm('¿Cerrar definitivamente el año {{ $year }}?')">
                    @csrf
                    <input type="text" name="notes" maxlength="1000" placeholder="Nota del cierre (opcional)" class="input-field mb-2">
                    <button type="submit" class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed" {{ $canCloseAnnual ? '' : 'disabled' }}>Cerrar Año</button>
                </form>
            @endif
        @else
            <span class="badge-{{ $annual->status === 'closed' ? 'danger' : 'success' }}">{{ $annual->status === 'closed' ? 'Cerrado' : 'Abierto' }}</span>
        @endif
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Rango</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($months as $period)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $monthNames[$period->month] }}</td>
                    <td class="text-sm text-slate-500">{{ $period->start_date->format('d/m/Y') }} — {{ $period->end_date->format('d/m/Y') }}</td>
                    <td>
                        @if($period->status === 'closed')
                            <span class="badge-danger">Cerrado</span>
                        @else
                            <span class="badge-success">Abierto</span>
                        @endif
                    </td>
                    <td class="text-xs text-slate-500">
                        @if($period->status === 'closed')
                            Por {{ $period->closedBy?->name }} el {{ $period->closed_at?->format('d/m/Y H:i') }}
                            @if($period->notes)<br><span class="italic">{{ $period->notes }}</span>@endif
                        @elseif(($pendingDrafts[$period->id] ?? 0) > 0)
                            <span class="text-amber-700">{{ $pendingDrafts[$period->id] }} borrador(es) pendiente(s)</span>
                        @elseif($period->end_date->isFuture())
                            Pendiente de finalización
                        @else
                            Listo para cerrar
                        @endif
                    </td>
                    <td class="text-center whitespace-nowrap">
                        @if($canClose)
                            @if($period->status === 'closed')
                                <form action="{{ route('contabilidad.periodos.reabrir', $period) }}" method="POST" class="inline" onsubmit="return confirm('¿Reabrir este período?')">
                                    @csrf
                                    <input type="hidden" name="notes" value="Reapertura manual autorizada">
                                    <button type="submit" class="text-indigo-600 hover:underline text-sm">Reabrir</button>
                                </form>
                            @elseif($period->end_date->isFuture())
                                <span class="text-slate-400 text-sm">Aún no finaliza</span>
                            @else
                                <form action="{{ route('contabilidad.periodos.cerrar', $period) }}" method="POST" class="inline" onsubmit="return confirm('¿Cerrar este período? No se podrán crear ni modificar movimientos con fecha dentro de él.')">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Cerrar</button>
                                </form>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection
