@extends('layouts.app')

@section('title', 'Flujo de Caja')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Flujo de Caja</h1>
            <p class="page-subtitle">Movimientos de Caja y Banco (método directo)</p>
        </div>
        @include('contabilidad._export_buttons', ['report' => 'flujo-caja'])
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-field">
        <input type="date" name="date_to" value="{{ $dateTo }}" class="input-field">
        <button type="submit" class="btn-primary md:w-fit">Filtrar</button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card p-4">
            <p class="text-sm text-slate-500">Saldo Inicial</p>
            <p class="text-xl font-bold">C$ {{ number_format($openingBalance, 2) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-sm text-slate-500">Movimiento Neto</p>
            <p class="text-xl font-bold {{ $netMovement < 0 ? 'text-red-600' : 'text-emerald-600' }}">C$ {{ number_format($netMovement, 2) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-sm text-slate-500">Saldo Final</p>
            <p class="text-xl font-bold text-blue-700">C$ {{ number_format($closingBalance, 2) }}</p>
        </div>
    </div>

    <div class="card p-4">
        <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wide mb-3">Resumen por Categoría</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            @foreach($byCategory as $category => $amount)
                <div class="flex justify-between text-sm border-b border-slate-100 py-1">
                    <span class="text-slate-600">{{ $category }}</span>
                    <span class="font-semibold {{ $amount < 0 ? 'text-red-600' : '' }}">C$ {{ number_format($amount, 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Referencia</th>
                    <th>Categoría</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td class="text-sm">{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                        <td class="text-slate-700">{{ $movement['concept'] }}</td>
                        <td class="text-sm text-slate-500">{{ $movement['reference'] }}</td>
                        <td class="text-sm">{{ $movement['category'] }}</td>
                        <td class="text-right {{ $movement['amount'] < 0 ? 'text-red-600' : '' }}">C$ {{ number_format($movement['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500 py-8">No hay movimientos de caja/banco en este período.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
