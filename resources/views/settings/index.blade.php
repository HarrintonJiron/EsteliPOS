@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="space-y-6" id="settings-dashboard">
    <nav class="flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('dashboard.general') }}" class="hover:text-indigo-600">Inicio</a>
        <span aria-hidden="true">/</span>
        <span class="font-medium text-slate-800" aria-current="page">Configuración</span>
    </nav>

    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-300">Centro administrativo</p>
                <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Configura el negocio desde un solo lugar</h1>
                <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">Las funciones terminadas están disponibles. Las demás están identificadas como “Próximamente” y se completarán en la versión 2.0.</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm">
                <p class="text-xs text-slate-300">Funciones disponibles</p>
                <p class="mt-1 text-2xl font-bold">{{ $stats['configured_sections'] }}/{{ $stats['total_sections'] }}</p>
                <p class="text-xs text-slate-300">resto previsto para la versión 2.0</p>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Resumen de configuración">
        <div class="card border-l-4 border-indigo-500 p-4">
            <p class="text-xs font-medium text-slate-500">Usuarios activos</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['active_users'] }}<span class="text-sm font-medium text-slate-400">/{{ $stats['total_users'] }}</span></p>
        </div>
        <div class="card border-l-4 border-emerald-500 p-4">
            <p class="text-xs font-medium text-slate-500">Módulos activos</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['active_modules'] }}<span class="text-sm font-medium text-slate-400">/{{ $stats['total_modules'] }}</span></p>
        </div>
        <div class="card border-l-4 border-violet-500 p-4">
            <p class="text-xs font-medium text-slate-500">Secciones accesibles</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['configured_sections'] }}<span class="text-sm font-medium text-slate-400">/{{ $stats['total_sections'] }}</span></p>
        </div>
        <div class="card border-l-4 {{ $stats['attention'] > 0 ? 'border-amber-500' : 'border-emerald-500' }} p-4">
            <p class="text-xs font-medium text-slate-500">Requieren atención</p>
            <p class="mt-1 text-2xl font-bold {{ $stats['attention'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $stats['attention'] }}</p>
        </div>
    </section>

    <section class="card p-4 sm:p-5" aria-label="Buscar y filtrar configuraciones">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <label for="settings-search" class="sr-only">Buscar configuraciones</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                <input id="settings-search" type="search" class="input-field pl-10" placeholder="Buscar usuarios, impuestos, respaldos…" autocomplete="off">
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Categorías">
                @foreach($categories as $key => $label)
                    <button type="button" class="settings-category whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition {{ $key === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}" data-category-filter="{{ $key }}" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Áreas de configuración</h2>
                <p class="text-sm text-slate-500"><span id="settings-result-count">{{ count($sections) }}</span> secciones encontradas</p>
            </div>
        </div>

        <div id="settings-grid" class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($sections as $section)
                <x-settings.card :section="$section" />
            @endforeach
        </div>

        <div id="settings-empty" class="card hidden px-6 py-12 text-center" role="status">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl" aria-hidden="true">🔎</div>
            <h3 class="mt-4 font-semibold text-slate-900">No encontramos esa configuración</h3>
            <p class="mt-1 text-sm text-slate-500">Prueba otro término o selecciona la categoría “Todas”.</p>
        </div>
    </section>

    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-slate-900">Actividad administrativa reciente</h2>
                <p class="text-xs text-slate-500">Últimos cambios registrados por el sistema</p>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $recentActivity->count() }}</span>
        </div>

        @forelse($recentActivity as $log)
            <div class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-0">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600" aria-hidden="true">↻</div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900">{{ $log->description ?: $log->action }}</p>
                    <p class="text-xs text-slate-500">{{ $log->user?->name ?? 'Sistema' }} · {{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <div class="px-6 py-10 text-center">
                <p class="font-medium text-slate-700">Todavía no hay actividad registrada</p>
                <p class="mt-1 text-sm text-slate-500">Las acciones administrativas aparecerán aquí cuando la auditoría sea integrada en cada sección.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const search = document.getElementById('settings-search');
        const cards = [...document.querySelectorAll('[data-settings-card]')];
        const filters = [...document.querySelectorAll('[data-category-filter]')];
        const count = document.getElementById('settings-result-count');
        const empty = document.getElementById('settings-empty');
        let category = 'all';

        const applyFilters = () => {
            const term = search.value.trim().toLocaleLowerCase('es');
            let visible = 0;

            cards.forEach(card => {
                const matchesCategory = category === 'all' || card.dataset.category === category;
                const matchesSearch = !term || card.dataset.search.includes(term);
                const show = matchesCategory && matchesSearch;
                card.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            count.textContent = visible;
            empty.classList.toggle('hidden', visible !== 0);
        };

        search.addEventListener('input', applyFilters);
        filters.forEach(button => button.addEventListener('click', () => {
            category = button.dataset.categoryFilter;
            filters.forEach(item => {
                const active = item === button;
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
                item.classList.toggle('bg-indigo-600', active);
                item.classList.toggle('text-white', active);
                item.classList.toggle('bg-slate-100', !active);
                item.classList.toggle('text-slate-600', !active);
            });
            applyFilters();
        }));
    })();
</script>
@endpush
