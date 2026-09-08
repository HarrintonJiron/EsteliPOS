@props([
    'href' => null,
    'label' => 'Regresar',
])

<a
    href="{{ $href ?? url()->previous() }}"
    {{ $attributes->merge(['class' => 'ui-back-button inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700']) }}
>
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    <span>{{ $label }}</span>
</a>
