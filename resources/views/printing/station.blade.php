@extends('layouts.app')

@section('title', 'Estación de impresión')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <h1 class="page-title">Estación de impresión central</h1>
        <p class="page-subtitle">Mantén esta pantalla abierta únicamente en la caja conectada a la impresora térmica.</p>
    </div>

    <section class="card p-6">
        <div class="flex items-center gap-4">
            <span id="stationLight" class="h-4 w-4 rounded-full bg-emerald-500"></span>
            <div>
                <p id="stationStatus" class="font-semibold text-slate-900">Estación activa, esperando tickets…</p>
                <p class="mt-1 text-sm text-slate-500"><span id="pendingCount">{{ $pendingCount }}</span> trabajo(s) en cola.</p>
            </div>
        </div>
        <div id="currentJob" class="mt-5 hidden rounded-xl bg-indigo-50 p-4 text-sm text-indigo-900"></div>
        <div id="stationError" class="mt-5 hidden rounded-xl bg-red-50 p-4 text-sm text-red-800"></div>
    </section>

    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <strong>Importante:</strong> la impresora térmica debe ser la predeterminada de Windows y el acceso directo de EsteliPOS debe abrir Edge/Chrome con impresión silenciosa. No abras esta estación en dos computadoras al mismo tiempo.
    </section>

    <iframe id="printFrame" title="Documento en impresión" class="fixed -left-[10000px] top-0 h-px w-px border-0"></iframe>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const nextUrl = @json(route('printing.next'));
    const completeTemplate = @json(route('printing.complete', ['printJob' => '__JOB__']));
    const csrf = @json(csrf_token());
    const frame = document.getElementById('printFrame');
    const status = document.getElementById('stationStatus');
    const current = document.getElementById('currentJob');
    const error = document.getElementById('stationError');
    const count = document.getElementById('pendingCount');
    let busy = false;
    let activeJob = null;
    let printTimeout = null;

    async function complete(success, message = null) {
        if (!activeJob) return;
        const job = activeJob;
        activeJob = null;
        window.clearTimeout(printTimeout);
        try {
            await fetch(completeTemplate.replace('__JOB__', job.id), {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                body: JSON.stringify({success, error: message}),
            });
        } finally {
            busy = false;
            current.classList.add('hidden');
            window.setTimeout(poll, 500);
        }
    }

    frame.addEventListener('load', () => {
        if (!activeJob) return;
        printTimeout = window.setTimeout(
            () => complete(false, 'No se recibió confirmación de impresión desde el navegador.'),
            45000,
        );
    });

    window.addEventListener('message', event => {
        if (event.source !== frame.contentWindow || event.data?.type !== 'estelipos-print-complete' || !activeJob) return;
        complete(true);
    });

    async function poll() {
        if (busy || document.hidden) return;
        busy = true;
        error.classList.add('hidden');
        try {
            const response = await fetch(nextUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            });
            if (!response.ok) throw new Error(`Respuesta ${response.status}`);
            const data = await response.json();
            if (!data.job) {
                status.textContent = 'Estación activa, esperando tickets…';
                count.textContent = '0';
                busy = false;
                return;
            }
            activeJob = data.job;
            status.textContent = 'Imprimiendo…';
            current.textContent = data.job.label;
            current.classList.remove('hidden');
            frame.src = data.job.url;
        } catch (exception) {
            busy = false;
            error.textContent = `No se pudo consultar la cola: ${exception.message}`;
            error.classList.remove('hidden');
        }
    }

    window.setInterval(poll, 2500);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    poll();
})();
</script>
@endpush
