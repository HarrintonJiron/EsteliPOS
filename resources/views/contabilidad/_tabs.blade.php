@php
    $tabs = [
        ['route' => 'contabilidad.dashboard', 'label' => 'Dashboard', 'match' => 'contabilidad.dashboard'],
        ['route' => 'contabilidad.cuentas.index', 'label' => 'Catálogo de Cuentas', 'match' => 'contabilidad.cuentas.*'],
        ['route' => 'contabilidad.asientos.index', 'label' => 'Asientos Contables', 'match' => 'contabilidad.asientos.*'],
        ['route' => 'contabilidad.diario.index', 'label' => 'Diario General', 'match' => 'contabilidad.diario.*'],
        ['route' => 'contabilidad.mayor.index', 'label' => 'Mayor General', 'match' => 'contabilidad.mayor.*'],
        ['route' => 'contabilidad.balance-comprobacion.index', 'label' => 'Balance de Comprobación', 'match' => 'contabilidad.balance-comprobacion.*'],
        ['route' => 'contabilidad.centros-costo.index', 'label' => 'Centros de Costo', 'match' => 'contabilidad.centros-costo.*'],
        ['route' => 'contabilidad.periodos.index', 'label' => 'Períodos Fiscales', 'match' => 'contabilidad.periodos.*'],
        ['route' => 'contabilidad.estado-resultados.index', 'label' => 'Estado de Resultados', 'match' => 'contabilidad.estado-resultados.*'],
        ['route' => 'contabilidad.balance-general.index', 'label' => 'Balance General', 'match' => 'contabilidad.balance-general.*'],
        ['route' => 'contabilidad.flujo-caja.index', 'label' => 'Flujo de Caja', 'match' => 'contabilidad.flujo-caja.*'],
    ];
@endphp

<div class="border-b border-slate-200 mb-6 overflow-x-auto no-print">
    <nav class="flex gap-6 whitespace-nowrap min-w-max">
        @foreach($tabs as $tab)
            <a href="{{ route($tab['route']) }}" class="tab-link {{ request()->routeIs($tab['match']) ? 'tab-link-active' : 'tab-link-inactive' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
