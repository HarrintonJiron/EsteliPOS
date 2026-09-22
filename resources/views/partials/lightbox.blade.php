{{--
  Visor de fotos dentro de la misma página (sin abrir pestañas nuevas).
  Uso: <a href="URL" data-lightbox data-lightbox-list='["url1","url2"]'><img …></a>
  Sin JavaScript el enlace simplemente abre la imagen.
--}}
<script>
(() => {
    let overlay = null;
    let image = null;
    let counter = null;
    let list = [];
    let index = 0;
    let lastFocus = null;

    const absolute = url => new URL(url, window.location.href).href;

    function build() {
        overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/85 p-4';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Visor de fotos');
        overlay.innerHTML = `
            <button type="button" data-lb-close class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-2xl leading-none text-slate-800 shadow hover:bg-white" aria-label="Cerrar">&times;</button>
            <button type="button" data-lb-prev class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-2xl text-slate-800 shadow hover:bg-white" aria-label="Foto anterior">&lsaquo;</button>
            <button type="button" data-lb-next class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-2xl text-slate-800 shadow hover:bg-white" aria-label="Foto siguiente">&rsaquo;</button>
            <img data-lb-image alt="" class="max-h-[88vh] max-w-[92vw] rounded-lg bg-white object-contain shadow-2xl">
            <span data-lb-counter class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-700"></span>`;
        document.body.appendChild(overlay);
        image = overlay.querySelector('[data-lb-image]');
        counter = overlay.querySelector('[data-lb-counter]');

        overlay.addEventListener('click', event => {
            if (event.target === overlay || event.target.closest('[data-lb-close]')) close();
            else if (event.target.closest('[data-lb-prev]')) step(-1);
            else if (event.target.closest('[data-lb-next]')) step(1);
        });
        document.addEventListener('keydown', event => {
            if (overlay.classList.contains('hidden')) return;
            if (event.key === 'Escape') close();
            if (event.key === 'ArrowLeft') step(-1);
            if (event.key === 'ArrowRight') step(1);
        });
    }

    function render() {
        image.src = list[index];
        const many = list.length > 1;
        overlay.querySelector('[data-lb-prev]').classList.toggle('hidden', !many);
        overlay.querySelector('[data-lb-next]').classList.toggle('hidden', !many);
        counter.textContent = many ? `${index + 1} de ${list.length}` : '';
        counter.classList.toggle('hidden', !many);
    }

    function step(delta) {
        if (list.length < 2) return;
        index = (index + delta + list.length) % list.length;
        render();
    }

    function open(link) {
        if (!overlay) build();
        let urls = [];
        try { urls = JSON.parse(link.dataset.lightboxList || '[]'); } catch (e) { urls = []; }
        list = urls.length ? urls.map(absolute) : [absolute(link.href)];
        index = Math.max(0, list.indexOf(absolute(link.href)));
        lastFocus = document.activeElement;
        render();
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        overlay.querySelector('[data-lb-close]').focus();
    }

    function close() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        image.removeAttribute('src');
        lastFocus?.focus?.();
    }

    document.addEventListener('click', event => {
        const link = event.target.closest('a[data-lightbox]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;
        event.preventDefault();
        open(link);
    });
})();
</script>
