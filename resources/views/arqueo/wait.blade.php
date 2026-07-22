@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-2xl bg-white shadow rounded-lg p-6 text-center">
        <h1 class="text-3xl font-bold mb-2">Arqueo de Caja</h1>
        <p class="text-sm text-slate-500 mb-4">Pantalla inicial de apertura/cierre. Registre el conteo físico y luego cierre la caja.</p>

        <div id="clock" class="text-8xl font-mono mb-2 leading-none"></div>
        <div id="date" class="text-xl text-slate-600 mb-6">{{ $now->format('d/m/Y') }}</div>

        <div class="flex justify-center mb-4 space-x-3">
            @if(!empty($openSession))
                <div class="text-sm text-slate-600 mr-2">Caja abierta desde: {{ $openSession->opened_at->format('H:i') ?? '-' }}</div>
            @endif

            <form id="openForm" method="POST" action="{{ route('arqueo.open') }}" class="inline">
                @csrf
                <input type="hidden" name="date" value="{{ \Carbon\Carbon::today()->toDateString() }}">
                @if(empty($openSession))
                    <button id="openBtn" type="submit" class="btn-primary px-6 py-2 text-lg">Abrir caja</button>
                @else
                    <button id="openBtn" type="button" class="btn-ghost px-6 py-2 text-lg" disabled>Caja Abierta</button>
                @endif
            </form>

            <button id="showCountBtn" class="btn-outline px-4 py-2">Registrar conteo</button>

            <a href="{{ url()->previous() }}" class="btn-outline px-4 py-2">Cancelar</a>
        </div>

        <form id="countForm" method="POST" action="{{ route('arqueo.run') }}" class="hidden bg-slate-50 p-4 rounded">
            @csrf
            <input type="hidden" name="date" value="{{ \Carbon\Carbon::today()->toDateString() }}">
            <input type="hidden" name="caja_session_id" value="{{ $openSession->id ?? '' }}">

            <div class="mb-2 text-left">
                <div class="text-sm font-semibold">Conteo físico (digita cantidades o usa el teclado)</div>
                <div class="text-xs text-slate-500">Pulsa una denominación para agregar 1 unidad, o mantén el teclado numérico para ingresar cantidades.</div>
            </div>

            <div id="denoms" class="grid grid-cols-2 gap-2 mb-3 text-sm">
                @foreach([100,50,20,10,5,1] as $i => $denom)
                <div class="flex items-center gap-2 p-2 bg-white rounded">
                    <button type="button" tabindex="-1" class="denom-btn w-20 h-10 bg-slate-100 rounded text-sm font-semibold" data-idx="{{ $i }}" data-amount="{{ $denom }}">C$ {{ $denom }}</button>
                    <input type="hidden" name="physical_counts[{{ $i }}][amount]" value="{{ $denom }}">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="physical_counts[{{ $i }}][qty]" value="0" class="qty-input input-field w-full text-center" />
                </div>
                @endforeach
            </div>

            <div id="numpad" class="grid grid-cols-3 gap-2 mb-3">
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">7</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">8</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">9</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">4</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">5</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">6</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">1</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">2</button>
                <button type="button" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl">3</button>
                <button type="button" id="pad-0" tabindex="-1" class="pad-btn p-3 bg-slate-100 rounded text-xl col-span-2">0</button>
                <button type="button" id="pad-back" tabindex="-1" class="p-3 bg-red-100 rounded text-xl">⌫</button>
            </div>

            <div class="flex justify-center space-x-3">
                <button type="submit" class="btn-primary">Cerrar caja y generar arqueo</button>
                <button type="button" id="hideCountBtn" class="btn-outline">Ocultar</button>
            </div>
        </form>

        <div id="overlay" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center">
            <div class="bg-white rounded p-6 w-80 text-center">
                <div class="animate-spin mb-4">⚙️</div>
                <div class="text-lg font-semibold">Procesando arqueo...</div>
                <div class="text-sm text-slate-500 mt-2">Espere mientras se compila el reporte.</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function pad(n){return n<10? '0'+n : n}
    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('clock');
        const date = document.getElementById('date');
        const hours = pad(now.getHours());
        const mins = pad(now.getMinutes());
        const secs = pad(now.getSeconds());
        if (clock) clock.textContent = `${hours}:${mins}:${secs}`;
        if (date) date.textContent = now.toLocaleDateString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    const openForm = document.getElementById('openForm');
    if (openForm) {
        openForm.addEventListener('submit', function(e){
            const overlay = document.getElementById('overlay');
            if (overlay) overlay.classList.remove('hidden');
        });
    }

    const showBtn = document.getElementById('showCountBtn');
    if (showBtn) {
        showBtn.addEventListener('click', function(e){
            const countForm = document.getElementById('countForm');
            if (countForm) countForm.classList.remove('hidden');
            this.classList.add('hidden');
            // focus first qty input
            const first = document.querySelector('.qty-input');
            if (first) first.focus();
        });
    }

    const hideBtn = document.getElementById('hideCountBtn');
    if (hideBtn) {
        hideBtn.addEventListener('click', function(){
            const countForm = document.getElementById('countForm');
            if (countForm) countForm.classList.add('hidden');
            const show = document.getElementById('showCountBtn');
            if (show) show.classList.remove('hidden');
        });
    }

    const countForm = document.getElementById('countForm');
    if (countForm) {
        countForm.addEventListener('submit', function(){
            const overlay = document.getElementById('overlay');
            if (overlay) overlay.classList.remove('hidden');
        });

        // Numeric keypad behavior
        let activeQty = null;
        let lastTouchedQty = null;

        // Keydown handling to direct typed numbers to active field
        countForm.addEventListener('keydown', function(e){
            if (!activeQty) return;
            const k = e.key;
            if ((/^[0-9]$/).test(k)) {
                e.preventDefault();
                activeQty.value = (activeQty.value === '0' ? k : activeQty.value + k);
                activeQty.dispatchEvent(new Event('input'));
                return;
            }
            if (k === 'Backspace') {
                e.preventDefault();
                activeQty.value = activeQty.value.slice(0,-1) || '0';
                activeQty.dispatchEvent(new Event('input'));
                return;
            }
        });

        document.querySelectorAll('.qty-input').forEach(function(inp){
            inp.addEventListener('focus', function(){ activeQty = this; this.select(); lastTouchedQty = this; });
            inp.addEventListener('pointerdown', function(){ activeQty = this; lastTouchedQty = this; });
            inp.addEventListener('touchstart', function(){ activeQty = this; lastTouchedQty = this; });
            inp.addEventListener('blur', function(){ activeQty = null; });
        });

        document.querySelectorAll('.pad-btn').forEach(function(b){
            b.addEventListener('click', function(){
                if (!activeQty) {
                    // prefer last touched input, then document.activeElement if usable; do NOT default to first
                    const f = lastTouchedQty || (document.activeElement && document.activeElement.classList && document.activeElement.classList.contains('qty-input') ? document.activeElement : null);
                    if (f) { f.focus(); activeQty = f; }
                }
                if (!activeQty) return;
                const val = this.textContent.trim();
                if (val === '0') {
                    activeQty.value = activeQty.value === '0' ? '0' : (activeQty.value + '0');
                } else if (val === '⌫') {
                    activeQty.value = activeQty.value.slice(0,-1) || '0';
                } else {
                    activeQty.value = (activeQty.value === '0' ? val : activeQty.value + val);
                }
                activeQty.dispatchEvent(new Event('input'));
            });
        });

        const back = document.getElementById('pad-back');
        if (back) back.addEventListener('click', function(){
            if (!activeQty) return; activeQty.value = activeQty.value.slice(0,-1) || '0';
        });

        // Denomination quick buttons: add 1 to qty
        document.querySelectorAll('.denom-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                const idx = btn.getAttribute('data-idx');
                const qty = document.querySelector(`input[name=\"physical_counts[${idx}][qty]\"]`);
                if (!qty) return;
                qty.value = String(Math.max(0, parseInt(qty.value || '0') + 1));
                qty.dispatchEvent(new Event('input'));
                qty.focus(); activeQty = qty; lastTouchedQty = qty;
            });
        });
    }
</script>
@endpush
