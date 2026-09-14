@extends('layouts.app')

@section('title', 'Balance General')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Balance General</h1>
            <p class="page-subtitle">Situación financiera a una fecha de corte</p>
        </div>
        @include('contabilidad._export_buttons', ['report' => 'balance-general'])
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="input-field">
        <button type="submit" class="btn-primary md:w-fit">Filtrar</button>
    </form>

    @php $balanced = round($diferencia, 2) === 0.0; @endphp

    <div class="card p-4 flex items-center gap-2 {{ $balanced ? 'border-l-4 border-emerald-500' : 'border-l-4 border-red-500' }}">
        @if($balanced)
            <span class="badge-success">Cuadrado</span>
            <span class="text-sm text-slate-600">Activo = Pasivo + Capital</span>
        @else
            <span class="badge-danger">Descuadrado</span>
            <span class="text-sm text-slate-600">Diferencia de C$ {{ number_format($diferencia, 2) }}</span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card overflow-hidden">
            <table class="min-w-full table-agro">
                <thead>
                    <tr><th colspan="3">Activo</th></tr>
                </thead>
                <tbody>
                    @forelse($activo as $account)
                        <tr>
                            <td class="font-mono w-24">{{ $account->code }}</td>
                            <td class="text-slate-700">{{ $account->name }}</td>
                            <td class="text-right">C$ {{ number_format($account->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-slate-500 py-4">Sin movimientos</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-bold bg-slate-100">
                        <td colspan="2" class="text-right">Total Activo</td>
                        <td class="text-right">C$ {{ number_format($totalActivo, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="space-y-6">
            <div class="card overflow-hidden">
                <table class="min-w-full table-agro">
                    <thead>
                        <tr><th colspan="3">Pasivo</th></tr>
                    </thead>
                    <tbody>
                        @forelse($pasivo as $account)
                            <tr>
                                <td class="font-mono w-24">{{ $account->code }}</td>
                                <td class="text-slate-700">{{ $account->name }}</td>
                                <td class="text-right">C$ {{ number_format($account->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-slate-500 py-4">Sin movimientos</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-bold bg-slate-100">
                            <td colspan="2" class="text-right">Total Pasivo</td>
                            <td class="text-right">C$ {{ number_format($totalPasivo, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="card overflow-hidden">
                <table class="min-w-full table-agro">
                    <thead>
                        <tr><th colspan="3">Capital</th></tr>
                    </thead>
                    <tbody>
                        @forelse($capital as $account)
                            <tr>
                                <td class="font-mono w-24">{{ $account->code }}</td>
                                <td class="text-slate-700">{{ $account->name }}</td>
                                <td class="text-right">C$ {{ number_format($account->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-slate-500 py-4">Sin movimientos</td></tr>
                        @endforelse
                        <tr>
                            <td colspan="2" class="text-slate-700">Utilidad del Ejercicio</td>
                            <td class="text-right">C$ {{ number_format($utilidadEjercicio, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold bg-slate-100">
                            <td colspan="2" class="text-right">Total Capital</td>
                            <td class="text-right">C$ {{ number_format($totalCapital, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
