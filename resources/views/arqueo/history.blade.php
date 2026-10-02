@extends('layouts.app')

@section('title', 'Historial de cierres')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div><h1 class="page-title">Historial de cierres</h1><p class="page-subtitle">Cierres inmutables y reimprimibles</p></div>
        <a href="{{ route('arqueo.index') }}" class="btn-outline">Caja actual</a>
    </div>
    <div class="card overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead><tr class="border-b text-left text-xs uppercase text-slate-500"><th class="p-3">Cierre</th><th>Cajero</th><th>Sucursal</th><th class="text-right">Esperado</th><th class="text-right">Contado</th><th class="text-right">Diferencia</th><th></th></tr></thead>
            <tbody>
            @forelse($arqueos as $arqueo)
                <tr class="border-b border-slate-100">
                    <td class="p-3">#{{ $arqueo->id }}<div class="text-xs text-slate-500">{{ $arqueo->closed_at?->format('d/m/Y H:i') ?? $arqueo->date->format('d/m/Y') }}</div></td>
                    <td>{{ $arqueo->user?->name ?? '—' }}</td><td>{{ $arqueo->branch?->name ?? '—' }}</td>
                    <td class="text-right tabular-nums">{{ $arqueo->currency }} {{ number_format((float) $arqueo->cash_total, 2) }}</td>
                    <td class="text-right tabular-nums">{{ number_format((float) $arqueo->physical_total, 2) }}</td>
                    <td class="text-right tabular-nums {{ (float) $arqueo->difference < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format((float) $arqueo->difference, 2) }}</td>
                    <td class="p-3 text-right"><a class="btn-outline text-xs" href="{{ route('arqueo.show', $arqueo) }}">Ver / imprimir</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-500">Todavía no hay cierres guardados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $arqueos->links() }}
</div>
@endsection
