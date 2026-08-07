@php
    $tabs = [
        ['route' => 'planilla.index', 'label' => 'Dashboard', 'match' => 'planilla.index'],
        ['route' => 'nomina.index', 'label' => 'Nómina', 'match' => 'nomina.*'],
        ['route' => 'leave.index', 'label' => 'Permisos', 'match' => 'leave.*'],
        ['route' => 'loans.index', 'label' => 'Préstamos', 'match' => 'loans.*'],
        ['route' => 'bonuses.index', 'label' => 'Bonos', 'match' => 'bonuses.*'],
        ['route' => 'deductions.index', 'label' => 'Deducciones', 'match' => 'deductions.*'],
        ['route' => 'employees.create', 'label' => '+ Empleado', 'match' => 'employees.create'],
    ];
@endphp

<nav class="card px-2 overflow-x-auto">
    <div class="flex min-w-max gap-1">
        @foreach($tabs as $tab)
            @php
                $active = request()->routeIs($tab['match']);
            @endphp
            <a href="{{ route($tab['route']) }}"
               class="tab-link whitespace-nowrap {{ $active ? 'tab-link-active' : 'tab-link-inactive' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
