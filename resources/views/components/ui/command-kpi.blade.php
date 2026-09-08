@props([
    'label',
    'value',
    'meta' => null,
    'stat' => null,
])

<article {{ $attributes->class('ex-kpi') }}>
    <p class="ex-kpi__label">{{ $label }}</p>
    <p class="ex-kpi__value" @if($stat) data-dashboard-stat="{{ $stat }}" @endif>{{ $value }}</p>
    @if($meta)
        <p class="ex-kpi__meta">{{ $meta }}</p>
    @endif
</article>
