@extends('layouts.app')

@section('title', 'Asiento ' . $entry->number)

@section('content')

<div class="max-w-4xl mx-auto space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Asiento {{ $entry->number }}</h1>
            <p class="page-subtitle">{{ $entry->date->format('d/m/Y') }} — {{ $entry->concept }}</p>
        </div>
        <div class="flex gap-2 items-start">
            @if($entry->status === 'draft')
                <span class="badge-warning">Borrador</span>
                @can('post', $entry)
                <form action="{{ route('contabilidad.asientos.post', $entry) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-primary text-sm">Contabilizar</button>
                </form>
                @endcan
            @elseif($entry->status === 'posted')
                <span class="badge-success">Contabilizado</span>
                @can('void', $entry)
                <form action="{{ route('contabilidad.asientos.void', $entry) }}" method="POST" onsubmit="return confirm('¿Anular este asiento?')">
                    @csrf
                    <button type="submit" class="btn-outline text-sm">Anular</button>
                </form>
                @endcan
            @else
                <span class="badge-danger">Anulado</span>
            @endif
        </div>
    </div>

    <div class="card p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div><p class="text-slate-500">Referencia</p><p class="font-semibold">{{ $entry->reference ?? '—' }}</p></div>
        <div><p class="text-slate-500">Usuario</p><p class="font-semibold">{{ $entry->user?->name }}</p></div>
        <div><p class="text-slate-500">Notas</p><p class="font-semibold">{{ $entry->notes ?? '—' }}</p></div>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Cuenta</th>
                    <th>Centro de Costo</th>
                    <th>Detalle</th>
                    <th class="text-right">Debe</th>
                    <th class="text-right">Haber</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $line)
                <tr>
                    <td>{{ $line->account->code }} - {{ $line->account->name }}</td>
                    <td class="text-slate-600">{{ $line->costCenter?->name ?? '—' }}</td>
                    <td class="text-slate-600">{{ $line->detail ?? '—' }}</td>
                    <td class="text-right">{{ $line->debit > 0 ? 'C$ '.number_format($line->debit, 2) : '' }}</td>
                    <td class="text-right">{{ $line->credit > 0 ? 'C$ '.number_format($line->credit, 2) : '' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-semibold">
                    <td colspan="3" class="text-right">Totales</td>
                    <td class="text-right">C$ {{ number_format($entry->total_debit, 2) }}</td>
                    <td class="text-right">C$ {{ number_format($entry->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <a href="{{ route('contabilidad.asientos.index') }}" class="btn-outline">← Volver</a>

</div>

@endsection
