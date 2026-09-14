@extends('layouts.app')

@section('title', 'Evaluaciones')

@section('content')
<div class="ex-shell">
    @include('planilla._nav')
    <x-ui.command-hero
        kicker="Desempeño"
        title="Evaluaciones"
        subtitle="Desempeño del semestre en curso"
        :compact="true"
    />
    @if(session('success'))<div class="card border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('planilla.edit'))
    <form method="POST" action="{{ route('rrhh.evaluations.store') }}" class="card grid gap-3 p-4 md:grid-cols-4">
        @csrf
        <div><label class="mb-1 block text-xs font-semibold">Colaborador</label><select name="employee_id" class="input-field w-full" required><option value="">Seleccione</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs font-semibold">Fecha</label><input type="date" name="evaluation_date" value="{{ now()->toDateString() }}" class="input-field w-full" required></div>
        <div><label class="mb-1 block text-xs font-semibold">Período</label><input name="period" value="{{ old('period', now()->format('Y').'-'.(now()->month <= 6 ? 'I' : 'II')) }}" maxlength="30" class="input-field w-full" required></div>
        <div><label class="mb-1 block text-xs font-semibold">Puntaje (0–100)</label><input type="number" name="score" min="0" max="100" value="{{ old('score') }}" class="input-field w-full" required></div>
        <div><label class="mb-1 block text-xs font-semibold">Fortalezas</label><textarea name="strengths" class="input-field w-full" rows="2"></textarea></div>
        <div><label class="mb-1 block text-xs font-semibold">Por mejorar</label><textarea name="improvements" class="input-field w-full" rows="2"></textarea></div>
        <div><label class="mb-1 block text-xs font-semibold">Comentarios</label><textarea name="comments" class="input-field w-full" rows="2"></textarea></div>
        <div class="flex items-end"><button class="btn-primary w-full justify-center">Registrar evaluación</button></div>
        @if($errors->any())<div class="md:col-span-4 text-sm text-red-600">{{ $errors->first() }}</div>@endif
    </form>
    @endif

    <div class="card overflow-hidden">
        <table class="table-agro hr-table min-w-full">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Puesto</th>
                    <th>Sucursal</th>
                    <th style="width: 28%">Puntaje</th>
                    <th class="text-right">Resultado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evaluations as $row)
                    <tr>
                        <td class="font-medium text-slate-800">{{ $row['employee']->name }}</td>
                        <td>{{ $row['employee']->position }}</td>
                        <td class="text-slate-500">{{ $row['employee']->branch?->name ?? 'Casa matriz' }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="w-8 text-sm font-bold text-indigo-700">{{ $row['score'] }}</span>
                                <div class="hr-scorebar flex-1"><span style="width: {{ $row['score'] }}%"></span></div>
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="{{ $row['score'] >= 90 ? 'badge-success' : ($row['score'] >= 80 ? 'badge-info' : 'badge-warning') }}">{{ $row['label'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">Todavía no hay evaluaciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
