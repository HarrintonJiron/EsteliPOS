@php
    /** @var \App\Models\Product|null $product */
    $product = $product ?? null;
    $maxImages = \App\Services\ProductGalleryService::MAX_IMAGES;
    $existingImages = $product?->exists ? $product->images : collect();
@endphp

<div class="relative bg-white p-4 rounded-xl shadow" id="galleryField" data-max="{{ $maxImages }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-lg font-semibold text-gray-700">Fotos del producto</h2>
        <span id="galleryCounter" class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800"></span>
    </div>
    <p class="text-sm text-gray-500 mb-4">
        Toca un cuadro vacío para agregar una foto (frente, parte trasera, detalles, daños). La primera es la portada
        que se muestra en el catálogo y en el punto de venta.
    </p>

    <div id="galleryGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
        @foreach($existingImages as $image)
            <div class="gallery-existing relative aspect-square overflow-hidden rounded-xl border-2 border-sky-200 bg-sky-50 transition" data-existing>
                <a href="{{ $image->url }}" data-lightbox data-lightbox-list='@json($existingImages->pluck('url')->values())'>
                    <img src="{{ $image->url }}" alt="Foto {{ $loop->iteration }} de {{ $product->name }}" class="absolute inset-0 h-full w-full object-contain p-1">
                </a>
                <span class="gallery-cover-badge absolute left-1.5 top-1.5 hidden rounded-md bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white shadow">Portada</span>
                <label class="absolute inset-x-0 bottom-0 flex cursor-pointer items-center gap-1.5 border-t border-sky-100 bg-white/95 px-2 py-1.5 text-xs font-semibold text-red-700">
                    <input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}"
                           class="gallery-remove rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="gallery-remove-text">Eliminar</span>
                </label>
            </div>
        @endforeach
    </div>

    <input id="product_images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1">

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <button type="button" id="galleryPick"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            + Agregar fotos
        </button>
        <span class="text-xs text-gray-500">JPG, PNG o WebP hasta 8 MB cada una. Se optimizan automáticamente.</span>
    </div>
    <p id="galleryLimitNote" class="mt-2 hidden text-xs font-semibold text-amber-700"></p>
    @error('images')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('images.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<script>
(function () {
    const root = document.getElementById('galleryField');
    if (!root) return;

    const max = Number(root.dataset.max);
    const input = document.getElementById('product_images');
    const grid = document.getElementById('galleryGrid');
    const counter = document.getElementById('galleryCounter');
    const note = document.getElementById('galleryLimitNote');
    const pick = document.getElementById('galleryPick');
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    const cameraIcon = '<svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 9a2 2 0 012-2h1.5l1-1.5h9l1 1.5H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3.2" stroke-width="1.8"/></svg>';
    let files = [];

    const existingCards = () => Array.from(root.querySelectorAll('[data-existing]'));
    const keptExisting = () => existingCards().filter(card => !card.querySelector('.gallery-remove').checked);

    function badge(text, color) {
        const span = document.createElement('span');
        span.className = 'absolute left-1.5 top-1.5 rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase text-white shadow ' + color;
        span.textContent = text;
        return span;
    }

    function sync() {
        const dt = new DataTransfer();
        files.forEach(file => dt.items.add(file));
        input.files = dt.files;

        grid.querySelectorAll('.gallery-new, .gallery-empty').forEach(el => el.remove());

        const kept = keptExisting();
        existingCards().forEach(card => {
            const removed = card.querySelector('.gallery-remove').checked;
            card.classList.toggle('opacity-40', removed);
            card.classList.toggle('border-red-300', removed);
            card.classList.toggle('border-sky-200', !removed);
            card.querySelector('.gallery-remove-text').textContent = removed ? 'Se eliminará · deshacer' : 'Eliminar';
            card.querySelector('.gallery-cover-badge').classList.toggle('hidden', card !== kept[0]);
        });

        files.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'gallery-new relative aspect-square overflow-hidden rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/50';

            const img = document.createElement('img');
            img.className = 'absolute inset-0 h-full w-full object-contain p-1';
            img.alt = file.name;
            img.src = URL.createObjectURL(file);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'absolute inset-x-0 bottom-0 border-t border-indigo-100 bg-white/95 px-2 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50';
            remove.textContent = 'Quitar';
            remove.addEventListener('click', () => { files.splice(index, 1); sync(); });

            card.append(img, remove, badge(kept.length + index === 0 ? 'Portada · nueva' : 'Nueva', 'bg-indigo-600'));
            grid.appendChild(card);
        });

        const total = kept.length + files.length;
        for (let slot = total + 1; slot <= max; slot++) {
            const empty = document.createElement('button');
            empty.type = 'button';
            empty.className = 'gallery-empty flex aspect-square flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-sky-300 bg-sky-50 text-sky-500 transition hover:border-sky-500 hover:bg-sky-100';
            empty.setAttribute('aria-label', 'Agregar foto ' + slot);
            empty.innerHTML = cameraIcon + '<span class="text-xs font-bold">Foto ' + slot + '</span><span class="text-[11px] font-medium">+ Agregar</span>';
            empty.addEventListener('click', () => input.click());
            grid.appendChild(empty);
        }

        counter.textContent = total + ' de ' + max + ' fotos';
        counter.className = 'rounded-full px-2.5 py-0.5 text-xs font-bold ' + (total >= max ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800');
        pick.disabled = total >= max;
        pick.classList.toggle('opacity-50', total >= max);
        pick.classList.toggle('cursor-not-allowed', total >= max);
    }

    input.addEventListener('change', () => {
        let skipped = 0;

        Array.from(input.files).forEach(file => {
            const duplicate = files.some(f => f.name === file.name && f.size === file.size);
            const valid = allowed.includes(file.type) && file.size <= 8 * 1024 * 1024;
            if (duplicate || !valid) { if (!valid) skipped++; return; }
            if (keptExisting().length + files.length >= max) { skipped++; return; }
            files.push(file);
        });

        note.textContent = skipped > 0
            ? 'Se omitieron ' + skipped + ' archivo(s): máximo ' + max + ' fotos, JPG/PNG/WebP de hasta 8 MB.'
            : '';
        note.classList.toggle('hidden', skipped === 0);
        sync();
    });

    pick.addEventListener('click', () => input.click());
    root.querySelectorAll('.gallery-remove').forEach(box => box.addEventListener('change', sync));
    sync();
})();
</script>
