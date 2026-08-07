@props([
    'title',
    'subtitle' => null,
    'backHref' => null,
    'backLabel' => 'Regresar',
])

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="min-w-0 flex-1">
        @if($backHref)
            <div class="mb-2">
                <x-ui.back-button :href="$backHref" :label="$backLabel" />
            </div>
        @endif
        @isset($breadcrumb)
            <nav class="breadcrumb" aria-label="Breadcrumb">{{ $breadcrumb }}</nav>
        @endisset
        <h1 class="page-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="page-actions">{{ $actions }}</div>
    @endif
</div>
