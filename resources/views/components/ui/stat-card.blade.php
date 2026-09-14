@props([
    'label',
    'value',
    'meta' => null,
    'accent' => '#0d9488',
    'valueClass' => '',
])

<div {{ $attributes->merge(['class' => 'kpi-card']) }} style="--kpi-accent: {{ $accent }}">
    <p class="kpi-label">{{ $label }}</p>
    <p class="kpi-value {{ $valueClass }}">{{ $value }}</p>
    @if($meta)
        <p class="kpi-meta">{{ $meta }}</p>
    @endif
</div>
