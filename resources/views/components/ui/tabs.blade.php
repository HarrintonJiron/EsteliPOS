@props([
    'items' => [],
    'param' => 'report_type',
])

<nav class="tabs-nav no-print" aria-label="Tipos de reporte">
    @foreach($items as $item)
        @php
            $query = array_merge(request()->except(['page']), [$param => $item['id']]);
            $isActive = ($item['active'] ?? false) || request($param, $items[0]['id'] ?? '') === $item['id'];
        @endphp
        <a href="{{ route(request()->route()->getName(), $query) }}"
           class="{{ $isActive ? 'is-active' : '' }}"
           @if($isActive) aria-current="page" @endif>
            @if(! empty($item['icon']))
                <span aria-hidden="true">{!! $item['icon'] !!}</span>
            @endif
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
