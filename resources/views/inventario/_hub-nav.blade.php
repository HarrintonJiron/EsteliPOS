@php
    $hubLinks = [
        [
            'route' => 'inventario.index',
            'label' => 'Catálogo',
            'match' => ['inventario.index', 'inventario.show', 'inventario.edit', 'inventario.create', 'inventario.quick', 'inventario.bulk'],
        ],
        [
            'route' => 'inventario.dashboard',
            'label' => 'Dashboard',
            'match' => ['inventario.dashboard'],
        ],
        [
            'route' => 'inventario.warehouses.index',
            'label' => 'Bodegas',
            'match' => ['inventario.warehouses.*'],
        ],
        [
            'route' => 'inventario.transfers.index',
            'label' => 'Transferencias',
            'match' => ['inventario.transfers.*'],
        ],
        [
            'route' => 'inventario.price-lists.index',
            'label' => 'Precios',
            'match' => ['inventario.price-lists.*'],
        ],
        [
            'route' => 'inventario.units.index',
            'label' => 'Unidades',
            'match' => ['inventario.units.*'],
        ],
        [
            'route' => 'movimientos.index',
            'label' => 'Kardex',
            'match' => ['movimientos.*'],
        ],
    ];

    $isHubActive = function (array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    };
@endphp

<nav class="inv-hub-nav" aria-label="Secciones de inventario">
    <div class="inv-hub-nav__track">
        @foreach($hubLinks as $link)
            @php $active = $isHubActive($link['match']); @endphp
            <a
                href="{{ route($link['route']) }}"
                @class([
                    'inv-hub-nav__item',
                    'is-active' => $active,
                ])
                @if($active) aria-current="page" @endif
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</nav>
