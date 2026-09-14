@extends('layouts.app')

@section('title', 'Sucursales')

@section('content')
@php
    $ranked = collect($scorecard)->sortByDesc('sales_month')->values();
    $totalSales = (float) $ranked->sum('sales_month');
    $totalStock = (float) $ranked->sum('stock_value');
    $totalPeople = (int) $ranked->sum('employees');
@endphp

<div class="ex-shell">
    <x-ui.command-hero
        kicker="Red comercial"
        title="Sucursales"
        subtitle="Administra los puntos operativos y consulta sus resultados reales"
        metric-label="Ventas de la red"
        :metric-value="'C$ ' . number_format($totalSales, 0)"
        :meta="[$ranked->count() . ' puntos', $totalPeople . ' personas']"
        :stats="[
            ['label' => 'Inventario', 'value' => 'C$ ' . number_format($totalStock, 0)],
            ['label' => 'Puntos', 'value' => (string) $ranked->count()],
            ['label' => 'Personal', 'value' => (string) $totalPeople],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('analitica.index') }}" class="ex-btn ex-btn--solid">Analítica gerencial</a>
            @if(auth()->user()?->isAdmin() || auth()->user()?->can('settings.update'))
                <a href="{{ route('sucursales.create') }}" class="ex-btn">Nueva sucursal</a>
            @endif
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="ex-panel no-print">
        <p class="ex-kicker" style="color:#4f46e5">Red configurada</p>
        <div class="flex flex-wrap items-center gap-2 text-sm mt-2">
            @forelse($ranked as $index => $row)
                @if($index > 0)
                    <span class="text-slate-300">→</span>
                @endif
                <span class="rounded-full bg-indigo-50 px-3 py-1 font-medium text-indigo-800">{{ $row['branch']->name }}</span>
            @empty
                <span class="text-slate-500">Agrega la primera sucursal para comenzar.</span>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($scorecard as $row)
            @php $branch = $row['branch']; @endphp
            <a href="{{ route('sucursales.show', $branch) }}" class="card p-5 hover:shadow-lg hover:border-indigo-300 transition border border-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-mono text-indigo-600">{{ $branch->code }}</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-800">{{ $branch->name }}</h2>
                        <p class="text-sm text-slate-500">{{ $branch->city }} · {{ $branch->type_label }}</p>
                    </div>
                    <span class="{{ $branch->is_active ? 'badge-success' : 'badge-danger' }}">{{ $branch->is_active ? 'Activa' : 'Inactiva' }}</span>
                </div>
                <p class="mt-3 text-sm text-slate-600">{{ $branch->address }}</p>
                <p class="mt-1 text-xs text-slate-500">Encargado: {{ $branch->manager_name }} · {{ $branch->phone }}</p>
                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4">
                    <div>
                        <p class="text-[11px] uppercase text-slate-400">Ventas</p>
                        <p class="font-semibold text-emerald-700">C$ {{ number_format($row['sales_month'], 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase text-slate-400">Inventario</p>
                        <p class="font-semibold text-slate-800">C$ {{ number_format($row['stock_value'], 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase text-slate-400">Personal</p>
                        <p class="font-semibold text-indigo-700">{{ $row['employees'] }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="card p-8 text-center text-slate-500 md:col-span-2 xl:col-span-3">
                <p>Aún no hay sucursales configuradas.</p>
                @if(auth()->user()?->isAdmin() || auth()->user()?->can('settings.update'))
                    <a href="{{ route('sucursales.create') }}" class="btn-primary mt-4">Crear primera sucursal</a>
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection
