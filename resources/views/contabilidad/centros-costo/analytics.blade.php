@extends('layouts.app')

@section('title', 'Análisis de centros de costo')

@section('content')
<div class="ex-shell">
    @include('contabilidad._tabs')

    <x-ui.command-hero
        kicker="Análisis de costos"
        :title="'Centros de costo · ' . $year"
        subtitle="Debe acumulado por mostrador, bodega, taller, administración y proyectos"
        :compact="true"
    >
        <x-slot:actions>
            <a href="{{ route('contabilidad.centros-costo.index') }}" class="ex-btn">Catálogo</a>
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($rows as $row)
            <div class="card p-5">
                <p class="text-xs font-mono text-indigo-600">{{ $row->code }}</p>
                <h3 class="mt-1 font-semibold text-slate-800">{{ $row->name }}</h3>
                <p class="text-xs text-slate-500">{{ $row->type_label }}</p>
                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <p class="text-slate-500">Debe</p>
                        <p class="font-semibold">C$ {{ number_format($row->debit, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Haber</p>
                        <p class="font-semibold">C$ {{ number_format($row->credit, 2) }}</p>
                    </div>
                </div>
                <p class="mt-3 text-lg font-bold text-violet-700">Neto C$ {{ number_format($row->net, 2) }}</p>
            </div>
        @empty
            <div class="card p-8 text-slate-500 xl:col-span-3">No hay pólizas con centro de costo en el año.</div>
        @endforelse
    </div>
</div>
@endsection
