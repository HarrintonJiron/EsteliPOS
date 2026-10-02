@php
    $galleryImages = $product->images;
    $gallerySlots = \App\Services\ProductGalleryService::MAX_IMAGES;
@endphp

@if($galleryImages->isNotEmpty())
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" id="productGallery">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <h2 class="font-bold text-slate-900">Fotos del producto</h2>
            <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800">{{ $galleryImages->count() }} de {{ $gallerySlots }}</span>
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-[minmax(0,1fr)_auto]">
            <a id="galleryMainLink" href="{{ $galleryImages->first()->url }}" data-lightbox data-lightbox-list='@json($galleryImages->pluck('url')->values())'
               class="flex h-72 items-center justify-center overflow-hidden rounded-2xl border-2 border-sky-200 bg-sky-50">
                <img id="galleryMain" src="{{ $galleryImages->first()->url }}" alt="Foto principal de {{ $product->name }}" class="h-full w-full object-contain">
            </a>

            <div class="flex gap-2 overflow-x-auto md:flex-col md:overflow-visible">
                @foreach($galleryImages as $image)
                    <button type="button" data-gallery-thumb data-url="{{ $image->url }}"
                            aria-label="Ver foto {{ $loop->iteration }}"
                            class="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-sky-50 transition {{ $loop->first ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-sky-200 hover:border-sky-400' }}">
                        <img src="{{ $image->url }}" alt="" class="h-full w-full object-cover">
                        <span class="absolute bottom-0 right-0 rounded-tl-md bg-white/90 px-1 text-[10px] font-bold text-slate-600">{{ $loop->iteration }}</span>
                    </button>
                @endforeach
                @for($slot = $galleryImages->count() + 1; $slot <= $gallerySlots; $slot++)
                    <div aria-hidden="true"
                         class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl border-2 border-dashed border-sky-200 bg-sky-50/60 text-sky-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 9a2 2 0 012-2h1.5l1-1.5h9l1 1.5H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3.2" stroke-width="1.8"/></svg>
                        <span class="text-[10px] font-semibold">{{ $slot }}</span>
                    </div>
                @endfor
            </div>
        </div>
    </section>
    <script>
        document.querySelectorAll('#productGallery [data-gallery-thumb]').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                document.getElementById('galleryMain').src = thumb.dataset.url;
                document.getElementById('galleryMainLink').href = thumb.dataset.url;
                document.querySelectorAll('#productGallery [data-gallery-thumb]').forEach(function (other) {
                    const active = other === thumb;
                    ['border-indigo-500', 'ring-2', 'ring-indigo-200'].forEach(function (c) { other.classList.toggle(c, active); });
                    ['border-sky-200', 'hover:border-sky-400'].forEach(function (c) { other.classList.toggle(c, !active); });
                });
            });
        });
    </script>
@endif
