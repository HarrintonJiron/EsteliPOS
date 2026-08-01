@extends('layouts.app')

@section('title', 'Balance de Comprobación')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Balance de Comprobación</h1>
            <p class="page-subtitle">Sumas y saldos por cuenta contabilizados</p>
        </div>
        @include('contabilidad._export_buttons', ['report' => 'balance-comprobacion'])
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Desde" class="input-field">
        <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="Hasta" class="input-field">
        <button type="submit" class="btn-primary md:w-fit">Filtrar</button>
    </form>

    @php $balanced = round($totalDebe, 2) === round($totalHaber, 2); @endphp

    <div class="card p-4 flex items-center gap-2 {{ $balanced ? 'border-l-4 border-emerald-500' : 'border-l-4 border-red-500' }}">
        @if($balanced)
            <span class="badge-success">Cuadrado</span>
            <span class="text-sm text-slate-600">El total del Debe es igual al total del Haber.</span>
        @else
            <span class="badge-danger">Descuadrado</span>
            <span class="text-sm text-slate-600">Diferencia de C$ {{ number_format($totalDebe - $totalHaber, 2) }}</span>
        @endif
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cuenta</th>
                    <th class="text-right">Debe</th>
                    <th class="text-right">Haber</th>
                    <th class="text-right">Diferencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td class="font-mono">{{ $account->code }}</td>
                    <td class="text-slate-700">{{ $account->name }}</td>
                    <td class="text-right">{{ number_format($account->debe, 2) }}</td>
                    <td class="text-right">{{ number_format($account->haber, 2) }}</td>
                    <td class="text-right {{ $account->diferencia < 0 ? 'text-red-600' : 'text-slate-700' }}">{{ number_format($account->diferencia, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-slate-500 py-8">No hay movimientos contabilizados en este período.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="font-semibold bg-slate-50">
                    <td colspan="2" class="text-right">Totales</td>
                    <td class="text-right">C$ {{ number_format($totalDebe, 2) }}</td>
                    <td class="text-right">C$ {{ number_format($totalHaber, 2) }}</td>
                    <td class="text-right">C$ {{ number_format($totalDebe - $totalHaber, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

@endsection
