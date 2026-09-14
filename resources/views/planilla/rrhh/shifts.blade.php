@extends('layouts.app')

@section('title', 'Turnos')

@section('content')
<div class="ex-shell">
    @include('planilla._nav')
    <x-ui.command-hero
        kicker="Talento"
        title="Turnos"
        subtitle="Cobertura de mostrador, bodega, patio y taller"
        :compact="true"
    />

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-2">
        @foreach($shifts as $shift)
            <div class="hr-card">
                <p class="hr-card__label">{{ $shift['label'] }}</p>
                <p class="mt-1 text-sm font-bold text-slate-800">{{ $shift['hours'] }}</p>
                <p class="text-[11px] text-slate-500">{{ $shift['days'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <table class="table-agro hr-table min-w-full">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Puesto</th>
                    <th>Sucursal</th>
                    <th>Turno</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                    @php
                        $key = str_contains(mb_strtolower($employee->position), 'caja') || str_contains(mb_strtolower($employee->position), 'vendedor')
                            ? 'mostrador'
                            : (str_contains(mb_strtolower($employee->position), 'bodega') || str_contains(mb_strtolower($employee->position), 'patio') ? 'bodega' : (str_contains(mb_strtolower($employee->position), 'técnico') ? 'taller' : 'completo'));
                    @endphp
                    <tr>
                        <td class="font-medium">{{ $employee->name }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->branch?->name ?? 'Matriz' }}</td>
                        <td><span class="hr-chip">{{ $shifts[$key]['label'] }} · {{ $shifts[$key]['hours'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
