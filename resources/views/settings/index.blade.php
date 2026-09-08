@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="ex-shell" id="settings-dashboard">
    <x-ui.command-hero
        kicker="Centro administrativo"
        title="Configura el negocio desde un solo lugar"
        subtitle="Las funciones terminadas están disponibles. Las demás están identificadas como “Próximamente” y se completarán en la versión 2.0."
        metric-label="Funciones disponibles"
        :metric-value="$stats['configured_sections'] . '/' . $stats['total_sections']"
        :stats="[
            ['label' => 'Usuarios', 'value' => $stats['active_users'] . '/' . $stats['total_users']],
            ['label' => 'Módulos', 'value' => $stats['active_modules'] . '/' . $stats['total_modules']],
            ['label' => 'Atención', 'value' => (string) $stats['attention']],
        ]"
    />

    <div class="ex-kpis ex-kpis--4">
        <x-ui.command-kpi label="Usuarios activos" :value="$stats['active_users'] . '/' . $stats['total_users']" />
        <x-ui.command-kpi label="Módulos activos" :value="$stats['active_modules'] . '/' . $stats['total_modules']" />
        <x-ui.command-kpi label="Secciones accesibles" :value="$stats['configured_sections'] . '/' . $stats['total_sections']" />
        <x-ui.command-kpi label="Requieren atención" :value="(string) $stats['attention']" />
    </div>

    <section class="card p-4 sm:p-5" aria-label="Buscar y filtrar configuraciones">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <label for="settings-search" class="sr-only">Buscar configuraciones</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                <input id="settings-search" type="search" class="input-field input-with-leading-icon" placeholder="Buscar usuarios, impuestos, respaldos…" autocomplete="off">
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
                const haystack = (card.dataset.search || '').toLocaleLowerCase('es');
                const matchesSearch = !term || haystack.includes(term);
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
