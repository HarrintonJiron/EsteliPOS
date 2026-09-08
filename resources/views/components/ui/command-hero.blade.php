@props([
    'kicker' => 'Sala de control',
    'title',
    'subtitle' => null,
    'live' => true,
    'liveLabel' => 'Operación en vivo',
    'metricLabel' => null,
    'metricValue' => null,
    'delta' => null,
    'deltaSuffix' => 'vs mes anterior',
    'meta' => [],
    'stats' => [],
    'compact' => false,
    'subtitleId' => null,
])

@php
    $hasMetric = filled($metricValue);
    $hasStats = count($stats) > 0;
    $hasAside = isset($aside);
    $showGrid = $hasMetric || $hasStats || $hasAside;
@endphp

<section {{ $attributes->class(['ex-hero', 'ex-hero--compact' => $compact]) }}>
    <div class="ex-hero__top">
        <div>
            <p class="ex-kicker">{{ $kicker }}</p>
            <h1 class="ex-hero__title">{{ $title }}</h1>
            @if($subtitle)
                <p @if($subtitleId) id="{{ $subtitleId }}" @endif class="ex-hero__sub">{{ $subtitle }}</p>
            @endif
        </div>
        @if($live)
            <div class="ex-hero__live">
                <span class="ex-pulse" aria-hidden="true"></span>
                {{ $liveLabel }}
            </div>
        @endif
    </div>

    @if($showGrid)
        <div class="ex-hero__grid">
            @if($hasMetric)
                <div>
                    <p class="ex-hero__label">{{ $metricLabel }}</p>
                    <p class="ex-hero__value">{{ $metricValue }}</p>
                    @if($delta !== null || count($meta))
                        <div class="ex-hero__meta">
                            @if($delta !== null)
                                <span class="ex-delta {{ (float) $delta >= 0 ? 'is-up' : 'is-down' }}">
                                    {{ (float) $delta >= 0 ? '+' : '' }}{{ number_format((float) $delta, 1) }}% {{ $deltaSuffix }}
                                </span>
                            @endif
                            @foreach($meta as $item)
                                <span>{{ $item }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if($hasAside)
                <div class="ex-spark">{{ $aside }}</div>
            @elseif($hasStats)
                <div class="ex-hero__stats" @if(! $hasMetric) style="grid-column: 1 / -1" @endif>
                    @foreach($stats as $stat)
                        <div>
                            <p class="ex-hero__label">{{ $stat['label'] }}</p>
                            <p>{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @isset($body)
        <div class="ex-hero__body">{{ $body }}</div>
    @endisset

    @isset($actions)
        <div class="ex-hero__actions no-print">{{ $actions }}</div>
    @endisset
</section>
