@php
    $groups = [
        'Operación' => [
            ['route' => 'contabilidad.dashboard', 'label' => 'Resumen', 'icon' => '⌂', 'match' => 'contabilidad.dashboard'],
            ['route' => 'contabilidad.asientos.index', 'label' => 'Asientos', 'icon' => '↔', 'match' => 'contabilidad.asientos.*'],
            ['route' => 'contabilidad.diario.index', 'label' => 'Diario', 'icon' => '▤', 'match' => 'contabilidad.diario.*'],
            ['route' => 'contabilidad.mayor.index', 'label' => 'Mayor', 'icon' => '≡', 'match' => 'contabilidad.mayor.*'],
        ],
        'Reportes' => [
            ['route' => 'contabilidad.balance-comprobacion.index', 'label' => 'Comprobación', 'icon' => '✓', 'match' => 'contabilidad.balance-comprobacion.*'],
            ['route' => 'contabilidad.estado-resultados.index', 'label' => 'Resultados', 'icon' => '↗', 'match' => 'contabilidad.estado-resultados.*'],
            ['route' => 'contabilidad.balance-general.index', 'label' => 'Balance', 'icon' => '◫', 'match' => 'contabilidad.balance-general.*'],
            ['route' => 'contabilidad.flujo-caja.index', 'label' => 'Flujo de caja', 'icon' => '≈', 'match' => 'contabilidad.flujo-caja.*'],
            ['route' => 'contabilidad.centros-costo.analytics', 'label' => 'Costos', 'icon' => '◎', 'match' => 'contabilidad.centros-costo.analytics'],
        ],
        'Configuración' => [
            ['route' => 'contabilidad.cuentas.index', 'label' => 'Cuentas', 'icon' => '#', 'match' => 'contabilidad.cuentas.*'],
            ['route' => 'contabilidad.centros-costo.index', 'label' => 'Centros', 'icon' => '◇', 'match' => ['contabilidad.centros-costo.index', 'contabilidad.centros-costo.create', 'contabilidad.centros-costo.edit']],
            ['route' => 'contabilidad.periodos.index', 'label' => 'Períodos', 'icon' => '◷', 'match' => 'contabilidad.periodos.*'],
        ],
    ];
@endphp
<div class="accounting-nav no-print" aria-label="Navegación contable">
    <div class="accounting-nav__brand"><span class="accounting-nav__mark">C$</span><span><strong>Centro financiero</strong><small>Control, análisis y cumplimiento</small></span></div>
    <div class="accounting-nav__scroll">
        @foreach($groups as $group => $tabs)
            <div class="accounting-nav__group"><span class="accounting-nav__group-label">{{ $group }}</span><div class="accounting-nav__links">
                @foreach($tabs as $tab)
                    @php($active = is_array($tab['match']) ? request()->routeIs(...$tab['match']) : request()->routeIs($tab['match']))
                    <a href="{{ route($tab['route']) }}" class="accounting-nav__link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif><span aria-hidden="true">{{ $tab['icon'] }}</span>{{ $tab['label'] }}</a>
                @endforeach
            </div></div>
        @endforeach
    </div>
</div>
