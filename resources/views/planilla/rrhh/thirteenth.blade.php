@extends('layouts.app')

@section('title', 'Aguinaldo y vacaciones')

@section('content')
<div class="ex-shell">
    @include('planilla._nav')
    <x-ui.command-hero
        kicker="Prestaciones"
        title="Aguinaldo y vacaciones"
        subtitle="13° mes, vacaciones e indemnización estimada"
        :compact="true"
    />

    <div class="card overflow-hidden">
        <table class="table-agro hr-table min-w-full">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Ingreso</th>
                    <th class="text-right">13° mes</th>
                    <th class="text-right">Vacaciones</th>
                    <th class="text-center">Días</th>
                    <th class="text-right">Indemnización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($thirteenth as $row)
                    @php $employee = $row['employee']; @endphp
                    <tr>
                        <td class="font-medium">{{ $employee->name }}</td>
                        <td class="text-slate-600">{{ $employee->hire_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-right">C$ {{ number_format($row['benefits']['thirteenth_month'], 0) }}</td>
                        <td class="text-right">C$ {{ number_format($row['benefits']['vacation_pay'], 0) }}</td>
                        <td class="text-center">{{ $row['vacation']['available_days'] }}</td>
                        <td class="text-right font-medium">C$ {{ number_format($row['benefits']['severance'], 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
