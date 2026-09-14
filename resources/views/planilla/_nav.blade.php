@php
    $primaryTabs = [
        ['route' => 'rrhh.hub', 'label' => 'RR.HH.', 'match' => 'rrhh.hub'],
        ['route' => 'planilla.index', 'label' => 'Planilla', 'match' => 'planilla.index'],
        ['route' => 'nomina.index', 'label' => 'Nómina', 'match' => 'nomina.*'],
        ['route' => 'rrhh.directory', 'label' => 'Directorio', 'match' => 'rrhh.directory'],
        ['route' => 'rrhh.organigram', 'label' => 'Organigrama', 'match' => 'rrhh.organigram'],
        ['route' => 'rrhh.attendance', 'label' => 'Asistencia', 'match' => 'rrhh.attendance'],
        ['route' => 'rrhh.shifts', 'label' => 'Turnos', 'match' => 'rrhh.shifts'],
        ['route' => 'rrhh.thirteenth', 'label' => 'Aguinaldo', 'match' => 'rrhh.thirteenth'],
        ['route' => 'rrhh.inss', 'label' => 'INSS / IR', 'match' => 'rrhh.inss'],
        ['route' => 'rrhh.evaluations', 'label' => 'Evaluaciones', 'match' => 'rrhh.evaluations'],
    ];
    $payrollTabs = [
        ['route' => 'leave.index', 'label' => 'Permisos', 'match' => 'leave.*'],
        ['route' => 'loans.index', 'label' => 'Préstamos', 'match' => 'loans.*'],
        ['route' => 'bonuses.index', 'label' => 'Bonos', 'match' => 'bonuses.*'],
        ['route' => 'deductions.index', 'label' => 'Deducciones', 'match' => 'deductions.*'],
    ];
@endphp

<nav class="inv-hub-nav" aria-label="Recursos humanos">
    <div class="inv-hub-nav__track">
        @foreach($primaryTabs as $tab)
            @php $active = request()->routeIs($tab['match']); @endphp
            <a href="{{ route($tab['route']) }}"
               @class(['inv-hub-nav__item', 'is-active' => $active])
               @if($active) aria-current="page" @endif>
                {{ $tab['label'] }}
            </a>
        @endforeach
        <span class="hr-nav__split" aria-hidden="true"></span>
        @foreach($payrollTabs as $tab)
            @php $active = request()->routeIs($tab['match']); @endphp
            <a href="{{ route($tab['route']) }}"
               @class(['inv-hub-nav__item', 'is-active' => $active])
               @if($active) aria-current="page" @endif>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
