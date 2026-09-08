@extends('layouts.app')

@section('title', 'Directorio de personal')

@section('content')
<div class="ex-shell">
    @include('planilla._nav')
    <x-ui.command-hero
        kicker="Talento"
        title="Directorio"
        :subtitle="$directory->count() . ' colaboradores · ' . ($companyProfile['company_name'] ?? 'EsteliPOS')"
        :compact="true"
    />

    <div class="card overflow-hidden">
        <table class="table-agro hr-table min-w-full">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Puesto</th>
                    <th>Sucursal</th>
                    <th>Contrato</th>
                    <th class="text-right">Salario</th>
                    <th>Contacto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($directory as $row)
                    @php
                        $employee = $row['employee'];
                        $parts = preg_split('/\s+/', trim((string) $employee->name)) ?: [];
                        $initials = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="hr-avatar">{{ $initials }}</span>
                                <span>
                                    <a href="{{ route('employees.show', $employee) }}" class="font-semibold text-slate-800 hover:text-indigo-700">{{ $employee->name }}</a>
                                    <p class="text-[11px] text-slate-500">{{ $employee->cedula }}</p>
                                </span>
                            </div>
                        </td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->branch?->name ?? 'Casa matriz' }}</td>
                        <td class="text-slate-600">{{ $employee->contract_type_label }} · {{ $employee->payment_frequency_label }}</td>
                        <td class="text-right font-medium">C$ {{ number_format($employee->salary, 0) }}</td>
                        <td class="text-slate-600">{{ $employee->phone }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
