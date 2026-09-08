@extends('layouts.app')

@section('title', 'Inventario')

@section('content')

<div class="space-y-3" id="catalogApp" data-search-url="{{ route('inventario.index') }}">

    @include('inventario._hub-nav')

    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Catálogo de productos</h2>
            <p class="text-xs text-slate-500">Búsqueda en tiempo real mientras escribes</p>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('inventario.export') }}" class="btn-outline text-xs py-1.5">Exportar</a>
            <a href="{{ route('inventario.quick') }}" class="btn-primary text-xs py-1.5">+ Rápido</a>
        </div>
    </div>

    {{-- KPIs compactos --}}
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:grid-cols-6">
        @foreach([
            ['Productos', $stats['total_products'], 'text-indigo-600'],
            ['Bajo', $stats['low_stock_count'], 'text-amber-600'],
            ['Sin stock', $stats['out_of_stock_count'], 'text-red-600'],
            ['Por vencer', $stats['expiring_soon_count'], 'text-orange-600'],
            ['Costo', 'C$ '.number_format($stats['total_inventory_value'], 0), 'text-violet-700'],
            ['Venta', 'C$ '.number_format($stats['total_sale_value'], 0), 'text-emerald-700'],
        ] as [$label, $value, $color])
        <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-2">
            <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ $label }}</p>
            <p class="text-sm font-bold {{ $color }}">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
        <span class="text-slate-500">{{ $periodDays }}d:</span>
        <span class="font-semibold text-emerald-700">+{{ number_format($movementStats['entries']) }} entradas</span>
        <span class="font-semibold text-red-600">−{{ number_format($movementStats['exits']) }} salidas</span>
        @if($discrepancyCount > 0)
            <a href="{{ route('inventario.index', ['stock_status' => 'discrepancy']) }}" class="badge-danger ml-auto">{{ $discrepancyCount }} discrepancias</a>
        @else
            <span class="badge-success ml-auto">Conciliado</span>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-1 border-b border-slate-200 pb-0.5">
        @foreach(['list' => 'Catálogo', 'top_sellers' => 'Top ventas', 'high_rotation' => 'Alta rot.', 'low_rotation' => 'Baja rot.', 'dead_stock' => 'Muerto'] as $key => $label)
        <a href="{{ route('inventario.index', array_merge(request()->except('page'), ['view' => $key])) }}"
           data-view-tab="{{ $key }}"
           class="catalog-view-tab rounded-t px-2.5 py-1.5 text-xs font-medium {{ $viewMode === $key ? 'bg-white text-indigo-700 ring-1 ring-slate-200 ring-b-white -mb-px' : 'text-slate-500 hover:text-slate-700' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <form method="GET" id="catalogFilters" class="space-y-2">
        <input type="hidden" name="view" id="catalogView" value="{{ $viewMode }}">

        <div class="inv-catalog-search">
            <svg class="inv-catalog-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="search"
                name="q"
                id="catalogSearch"
                value="{{ request('q') }}"
                placeholder="Buscar por código, nombre, lote..."
                class="input-field inv-catalog-search__input py-2 text-sm"
                autocomplete="off"
            />
            <span id="catalogSearchStatus" class="inv-catalog-search__status hidden">Buscando…</span>
        </div>

        <details class="group card" @if(request()->hasAny(['category_id','warehouse_id','base_unit_id','stock_status','period']) && !request('q')) open @endif>
            <summary class="cursor-pointer px-3 py-2 text-xs font-medium text-slate-600">Más filtros</summary>
            <div class="grid grid-cols-2 gap-2 border-t border-slate-100 p-3 md:grid-cols-5 md:items-end">
                <select name="category_id" class="catalog-filter select-field py-1.5 text-sm">
                    <option value="">Categoría</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="warehouse_id" class="catalog-filter select-field py-1.5 text-sm">
                    <option value="">Bodega</option>
                    @foreach($warehouses ?? [] as $wh)
                    <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                    @endforeach
                </select>
                <select name="stock_status" class="catalog-filter select-field py-1.5 text-sm">
                    <option value="">Estado stock</option>
                    <option value="low" @selected(request('stock_status') == 'low')>Bajo stock</option>
                    <option value="out_of_stock" @selected(request('stock_status') == 'out_of_stock')>Sin stock</option>
                    <option value="expiring_soon" @selected(request('stock_status') == 'expiring_soon')>Por vencer</option>
                    <option value="discrepancy" @selected(request('stock_status') == 'discrepancy')>Discrepancia</option>
                </select>
                <select name="period" class="catalog-filter select-field py-1.5 text-sm">
                    @foreach([7, 30, 60, 90] as $d)
                    <option value="{{ $d }}" @selected($periodDays == $d)>Análisis {{ $d }}d</option>
                    @endforeach
                </select>
                <a href="{{ route('inventario.index') }}" class="btn-outline py-1.5 text-center text-xs">Limpiar</a>
            </div>
        </details>
    </form>

    <div class="card overflow-hidden" id="catalogResults">
        @include('inventario.partials.catalog-results')
    </div>
</div>

@push('scripts')
<script>
(() => {
    const app = document.getElementById('catalogApp');
    const form = document.getElementById('catalogFilters');
    const searchInput = document.getElementById('catalogSearch');
    const statusEl = document.getElementById('catalogSearchStatus');
    const resultsEl = document.getElementById('catalogResults');
    const viewInput = document.getElementById('catalogView');
    const baseUrl = app.dataset.searchUrl;
    let debounceTimer = null;
    let activeController = null;

    async function fetchCatalog(url = null) {
        const params = new URLSearchParams(new FormData(form));
        params.set('live', '1');

        const targetUrl = url ?? `${baseUrl}?${params.toString()}`;

        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();
        statusEl.classList.remove('hidden');

        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                signal: activeController.signal,
            });

            if (!response.ok) {
                throw new Error('Error al buscar');
            }

            resultsEl.innerHTML = await response.text();

            if (!url) {
                const browserParams = new URLSearchParams(new FormData(form));
                history.replaceState({}, '', `${baseUrl}?${browserParams.toString()}`);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                statusEl.textContent = 'Error';
            }
        } finally {
            statusEl.classList.add('hidden');
            statusEl.textContent = 'Buscando…';
            activeController = null;
        }
    }

    function scheduleSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchCatalog(), 280);
    }

    searchInput?.addEventListener('input', scheduleSearch);

    form.querySelectorAll('.catalog-filter').forEach((field) => {
        field.addEventListener('change', () => fetchCatalog());
    });

    document.querySelectorAll('.catalog-view-tab').forEach((tab) => {
        tab.addEventListener('click', (event) => {
            event.preventDefault();
            viewInput.value = tab.dataset.viewTab;
            document.querySelectorAll('.catalog-view-tab').forEach((el) => {
                el.classList.remove('bg-white', 'text-indigo-700', 'ring-1', 'ring-slate-200', 'ring-b-white', '-mb-px');
                el.classList.add('text-slate-500');
            });
            tab.classList.add('bg-white', 'text-indigo-700', 'ring-1', 'ring-slate-200', 'ring-b-white', '-mb-px');
            tab.classList.remove('text-slate-500');
            fetchCatalog();
        });
    });

    resultsEl.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link || !link.href) {
            return;
        }

        if (link.closest('nav[role="navigation"]')) {
            event.preventDefault();
            fetchCatalog(link.href);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && document.activeElement !== searchInput) {
            const tag = document.activeElement?.tagName?.toLowerCase();
            if (tag !== 'input' && tag !== 'textarea' && tag !== 'select') {
                event.preventDefault();
                searchInput?.focus();
                searchInput?.select();
            }
        }
    });
})();
</script>
@endpush
@endsection
