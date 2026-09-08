@props([
    'action' => null,
    'method' => 'GET',
])

<form {{ $attributes->merge(['class' => 'filter-panel']) }} action="{{ $action }}" method="{{ $method }}">
    <div class="filter-grid">
        {{ $slot }}
    </div>
    @if(isset($footer))
        <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
            {{ $footer }}
        </div>
    @endif
</form>
