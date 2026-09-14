@extends('layouts.app')

@section('title', $branch->name)

@section('content')
<div class="ex-shell">
    <x-ui.command-hero
        kicker="Punto de venta"
        :title="$branch->name"
        :subtitle="$branch->type_label . ' · ' . $branch->city"
        metric-label="Ventas del mes"
        :metric-value="'C$ ' . number_format($metrics['sales_month'] ?? 0, 0)"
        :meta="[($metrics['share'] ?? $branch->share_percent) . '% de la red']"
        :stats="[
            ['label' => 'Inventario', 'value' => 'C$ ' . number_format($metrics['stock_value'] ?? 0, 0)],
            ['label' => 'Personal', 'value' => (string) ($metrics['employees'] ?? $branch->employees->count())],
            ['label' => 'Participación', 'value' => ($metrics['share'] ?? $branch->share_percent) . '%'],
        ]"
        :compact="true"
    >
        <x-slot:actions>
            <a href="{{ route('sucursales.index') }}" class="ex-btn">Todas las sucursales</a>
            @if(auth()->user()?->isAdmin() || auth()->user()?->can('settings.update'))
                <a href="{{ route('sucursales.edit', $branch) }}" class="ex-btn ex-btn--solid">Editar</a>
            @endif
        </x-slot:actions>
    </x-ui.command-hero>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="card p-5 xl:col-span-2 space-y-3">
            <h3 class="data-card-title">Ficha del punto de venta</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-500">Dirección</dt><dd class="font-medium text-slate-800">{{ $branch->address }}</dd></div>
                <div><dt class="text-slate-500">Teléfono</dt><dd class="font-medium text-slate-800">{{ $branch->phone }}</dd></div>
                <div><dt class="text-slate-500">Encargado</dt><dd class="font-medium text-slate-800">{{ $branch->manager_name }}</dd></div>
                <div><dt class="text-slate-500">Bodega ligada</dt><dd class="font-medium text-slate-800">{{ $branch->warehouse?->name ?? 'Sin bodega propia (despacho desde matriz)' }}</dd></div>
                <div><dt class="text-slate-500">Centro de costo</dt><dd class="font-medium text-slate-800">{{ $branch->costCenter?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Estado</dt><dd>@if($branch->is_active)<span class="badge-success">Operando</span>@else<span class="badge-danger">Inactiva</span>@endif</dd></div>
            </dl>
            @if($branch->notes)
                <p class="text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $branch->notes }}</p>
            @endif
        </div>
        <div class="card p-5">
            <h3 class="data-card-title mb-3">Equipo en sitio</h3>
            <ul class="space-y-2">
                @forelse($branch->employees as $employee)
                    <li class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-800">{{ $employee->name }}</span>
                        <span class="text-slate-500">{{ $employee->position }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Personal rotativo desde casa matriz.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
