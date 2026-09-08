@props(['section'])

@php
    $toneClasses = match ($section['tone']) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
        default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    };
    $searchText = \Illuminate\Support\Str::lower($section['title'].' '.$section['description'].' '.$section['status'].' '.$section['metric']);
@endphp

<article
    class="settings-card card group flex min-h-64 flex-col overflow-hidden border border-slate-200 transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg"
    data-settings-card
    data-category="{{ $section['category'] }}"
    data-search="{{ $searchText }}"
>
    <div class="flex flex-1 flex-col p-5 sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-4">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-2xl" aria-hidden="true">{{ $section['icon'] }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $toneClasses }}">
                @if($section['attention'])
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                @endif
                {{ $section['status'] }}
            </span>
        </div>

        <div class="flex-1">
            <h2 class="text-lg font-semibold text-slate-900">{{ $section['title'] }}</h2>
            <p class="mt-1.5 text-sm leading-6 text-slate-500">{{ $section['description'] }}</p>
        </div>

        <div class="mt-5 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Estado actual</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-700">{{ $section['metric'] }}</p>
            </div>

            @if($section['url'])
                <a href="{{ $section['url'] }}" class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded">
                    {{ $section['action'] }}
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <span class="cursor-not-allowed text-sm font-medium text-slate-400" aria-disabled="true">{{ $section['action'] }}</span>
            @endif
        </div>
    </div>
</article>
