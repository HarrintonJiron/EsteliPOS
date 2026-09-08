@extends('layouts.app')

@section('title', 'Estado de Resultados')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Estado de Resultados</h1>
            <p class="page-subtitle">Ingresos, costos y gastos del período</p>
        </div>
        @include('contabilidad._export_buttons', ['report' => 'estado-resultados'])
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-field">
        <input type="date" name="date_to" value="{{ $dateTo }}" class="input-field">
        <button type="submit" class="btn-primary md:w-fit">Filtrar</button>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <tbody>
                @include('contabilidad.estado-resultados._section', ['title' => 'Ingresos', 'accounts' => $ingresos, 'total' => $totalIngresos])
                @include('contabilidad.estado-resultados._section', ['title' => 'Costos', 'accounts' => $costos, 'total' => $totalCostos])
                <tr class="bg-slate-100 font-semibold">
                    <td colspan="2" class="text-right">Utilidad Bruta</td>
                    <td class="text-right {{ $utilidadBruta < 0 ? 'text-red-600' : '' }}">C$ {{ number_format($utilidadBruta, 2) }}</td>
                </tr>

                @include('contabilidad.estado-resultados._section', ['title' => 'Gastos', 'accounts' => $gastos, 'total' => $totalGastos])
                <tr class="bg-slate-100 font-semibold">
                    <td colspan="2" class="text-right">Utilidad Operativa</td>
                    <td class="text-right {{ $utilidadOperativa < 0 ? 'text-red-600' : '' }}">C$ {{ number_format($utilidadOperativa, 2) }}</td>
                </tr>

                @include('contabilidad.estado-resultados._section', ['title' => 'Otros Ingresos', 'accounts' => $otrosIngresos, 'total' => $totalOtrosIngresos])
                @include('contabilidad.estado-resultados._section', ['title' => 'Otros Gastos', 'accounts' => $otrosGastos, 'total' => $totalOtrosGastos])

                <tr class="bg-emerald-50 font-bold text-emerald-800">
                    <td colspan="2" class="text-right">Utilidad Neta</td>
                    <td class="text-right {{ $utilidadNeta < 0 ? 'text-red-600' : '' }}">C$ {{ number_format($utilidadNeta, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

@endsection
