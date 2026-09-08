@extends('layouts.app')

@section('title', 'Centro de ayuda')

@section('content')
<div class="space-y-6" id="help-center">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-950 via-slate-900 to-cyan-950 p-6 text-white shadow-lg sm:p-8">
        <div class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Ayuda sin Internet</p>
            <h1 class="mt-2 text-2xl font-bold sm:text-3xl">¿Cómo podemos ayudarte?</h1>
            <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">Busca una tarea o un problema común. Las respuestas explican pasos seguros y cuándo debes comunicarte con el administrador.</p>
            <div class="relative mt-6 max-w-2xl">
                <label for="help-search" class="sr-only">Buscar en el centro de ayuda</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                <input id="help-search" type="search" class="input-field input-with-leading-icon !bg-white !py-3 !text-slate-900 shadow-sm outline-none ring-cyan-400 placeholder:text-slate-400 focus:ring-2" placeholder="Ejemplo: no imprime el ticket" autocomplete="off">
            </div>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Temas de ayuda">
        <button type="button" class="help-category rounded-xl bg-indigo-600 px-4 py-3 text-left text-sm font-semibold text-white shadow-sm" data-help-category="all" aria-pressed="true">Todos los temas</button>
        @foreach($categories as $key => $label)
            <button type="button" class="help-category card px-4 py-3 text-left text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700" data-help-category="{{ $key }}" aria-pressed="false">{{ $label }}</button>
        @endforeach
    </section>

    <section>
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Preguntas frecuentes</h2>
                <p class="mt-1 text-sm text-slate-500"><span id="help-result-count">{{ count($articles) }}</span> respuestas disponibles</p>
            </div>
            <button type="button" id="help-expand-all" class="btn-outline btn-sm">Abrir todas</button>
        </div>

        <div class="space-y-3" id="help-articles">
            @foreach($articles as $article)
                @php
                    $searchText = \Illuminate\Support\Str::lower($article['question'].' '.implode(' ', $article['steps']).' '.implode(' ', $article['keywords']));
                @endphp
                <details class="help-article card group overflow-hidden" data-help-article data-category="{{ $article['category'] }}" data-search="{{ $searchText }}">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                        <span>{{ $article['question'] }}</span>
                        <span class="text-xl text-indigo-500 transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-4">
                        <ol class="space-y-3">
                            @foreach($article['steps'] as $step)
                                <li class="flex gap-3 text-sm leading-6 text-slate-700">
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $loop->iteration }}</span>
                                    <span>{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </details>
            @endforeach
        </div>

        <div id="help-empty" class="card hidden px-6 py-12 text-center" role="status">
            <div class="text-3xl" aria-hidden="true">🔎</div>
            <h3 class="mt-3 font-semibold text-slate-900">No encontramos esa pregunta</h3>
            <p class="mt-1 text-sm text-slate-500">Prueba palabras más cortas o consulta con el administrador del sistema.</p>
        </div>
    </section>

    <section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 sm:flex sm:items-center sm:justify-between sm:gap-6">
        <div>
            <h2 class="font-bold text-cyan-950">¿El problema continúa?</h2>
            <p class="mt-1 text-sm leading-6 text-cyan-900">Anota la pantalla, hora, usuario, documento y mensaje exacto. Evita incluir contraseñas en capturas o mensajes.</p>
        </div>
        <button type="button" class="btn-outline mt-4 shrink-0 sm:mt-0" data-help-open-support>Ver datos necesarios</button>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const search = document.getElementById('help-search');
        const articles = [...document.querySelectorAll('[data-help-article]')];
        const categories = [...document.querySelectorAll('[data-help-category]')];
        const count = document.getElementById('help-result-count');
        const empty = document.getElementById('help-empty');
        const expand = document.getElementById('help-expand-all');
        let category = 'all';
        let expanded = false;

        const filter = () => {
            const term = search.value.trim().toLocaleLowerCase('es');
            let visible = 0;
            articles.forEach(article => {
                const show = (category === 'all' || article.dataset.category === category)
                    && (!term || article.dataset.search.includes(term));
                article.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            count.textContent = visible;
            empty.classList.toggle('hidden', visible !== 0);
        };

        search.addEventListener('input', filter);
        categories.forEach(button => button.addEventListener('click', () => {
            category = button.dataset.helpCategory;
            categories.forEach(item => {
                const active = item === button;
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
                item.classList.toggle('bg-indigo-600', active);
                item.classList.toggle('text-white', active);
                item.classList.toggle('card', !active);
                item.classList.toggle('text-slate-700', !active);
            });
            filter();
        }));

        expand.addEventListener('click', () => {
            expanded = !expanded;
            articles.filter(article => !article.classList.contains('hidden')).forEach(article => article.open = expanded);
            expand.textContent = expanded ? 'Cerrar todas' : 'Abrir todas';
        });

        document.querySelector('[data-help-open-support]').addEventListener('click', () => {
            search.value = 'soporte';
            category = 'all';
            filter();
            search.scrollIntoView({ behavior: 'smooth', block: 'center' });
            search.focus();
        });
    })();
</script>
@endpush
