@extends('layouts.app')

@section('title', 'Asistencia')

@section('content')
<div class="ex-shell">
    @include('planilla._nav')
    <x-ui.command-hero
        kicker="Talento"
        title="Asistencia"
        subtitle="Últimas jornadas hábiles · presente, tardanza y ausencia"
        :compact="true"
    />
    @if(session('success'))<div class="card border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('planilla.edit'))
    <form method="POST" action="{{ route('rrhh.attendance.store') }}" class="card grid gap-3 p-4 md:grid-cols-6">
        @csrf
        <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold">Colaborador</label><select name="employee_id" class="input-field w-full" required><option value="">Seleccione</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs font-semibold">Fecha</label><input type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" class="input-field w-full" required></div>
        <div><label class="mb-1 block text-xs font-semibold">Estado</label><select name="status" class="input-field w-full"><option value="present">Presente</option><option value="late">Tardanza</option><option value="absent">Ausente</option><option value="leave">Permiso</option></select></div>
        <div><label class="mb-1 block text-xs font-semibold">Entrada</label><input type="time" name="check_in" class="input-field w-full"></div>
        <div><label class="mb-1 block text-xs font-semibold">Salida</label><input type="time" name="check_out" class="input-field w-full"></div>
        <div class="md:col-span-5"><label class="mb-1 block text-xs font-semibold">Observación</label><input name="notes" value="{{ old('notes') }}" maxlength="500" class="input-field w-full" placeholder="Opcional"></div>
        <div class="flex items-end"><button class="btn-primary w-full justify-center">Guardar</button></div>
        @if($errors->any())<div class="md:col-span-6 text-sm text-red-600">{{ $errors->first() }}</div>@endif
    </form>
    @endif

    <div class="card overflow-x-auto">
        <table class="table-agro hr-table min-w-full">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    @foreach($attendance_days as $day)
                        <th class="text-center font-medium">{{ $day->format('d') }}</th>
                    @endforeach
                    <th class="text-center">P/T/A</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendance as $row)
                    <tr>
                        <td class="font-medium text-slate-800 whitespace-nowrap">{{ $row['employee']->name }}</td>
                        @foreach($row['days'] as $day)
                            <td class="text-center">
                                @if($day['status'])<span class="hr-dot is-{{ $day['status'] }}" title="{{ $day['status'] }}"></span>@else<span class="text-slate-300">—</span>@endif
                            </td>
                        @endforeach
                        <td class="text-center whitespace-nowrap text-slate-500">{{ $row['present'] }}/{{ $row['late'] }}/{{ $row['absent'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
