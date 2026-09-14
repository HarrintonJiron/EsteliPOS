@extends('layouts.app')

@section('title', 'Organigrama')

@section('content')
<div class="ex-shell">
    @include('planilla._nav')
    <x-ui.command-hero
        kicker="Talento"
        title="Organigrama"
        subtitle="Estructura por puesto en la red de sucursales"
        :compact="true"
    />

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
        @foreach($organigram as $position => $people)
            <div class="hr-card">
                <div class="flex items-baseline justify-between gap-2">
                    <p class="hr-card__label">{{ $position }}</p>
                    <span class="text-[11px] text-slate-400">{{ $people->count() }}</span>
                </div>
                <ul class="mt-2 flex flex-wrap gap-1.5">
                    @foreach($people as $employee)
                        <li class="hr-chip" title="{{ $employee->branch?->name ?? 'Matriz' }}">
                            {{ $employee->name }}
                            <span class="font-normal text-slate-400">{{ $employee->branch?->name ?? 'Matriz' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
@endsection
