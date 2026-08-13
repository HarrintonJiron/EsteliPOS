@extends('layouts.app')

@section('content')
@php
    $summary = $closingSummary ?? null;
    $denoms = $denominations ?? [1000, 500, 200, 100, 50, 20, 10, 5, 1];
@endphp
<div class="p-3 sm:p-4">
    <div class="mx-auto w-full max-w-3xl space-y-3">
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">{{ session('warning') }}</div>
        @endif

        @if(empty($openSession))
            {{-- Apertura compacta: primer paso del día --}}
            <form id="openForm" method="POST" action="{{ route('arqueo.open') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-900 px-4 py-3 text-white">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-300">Inicio del día</p>
                        <h1 class="text-lg font-bold leading-tight">Abrir caja</h1>
                    </div>
                    <div class="text-right">
                        <div id="clock" class="font-mono text-xl font-bold leading-none">{{ $now->format('H:i:s') }}</div>
                        <div id="date" class="mt-1 text-xs text-slate-300">{{ $now->format('d/m/Y') }}</div>
                    </div>
                </div>

                <div class="space-y-3 p-4">
                    <div>
                        <label for="opening_amount" class="mb-1 block text-sm font-semibold text-slate-700">¿Con cuánto efectivo abre?</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base font-bold text-slate-500">C$</span>
                            <input
                                id="opening_amount"
                                name="opening_amount"
                                type="text"
                                inputmode="decimal"
                                autocomplete="off"
                                value="{{ old('opening_amount', '') }}"
                                placeholder="0.00"
                                class="input-field input-with-leading-prefix w-full py-3 text-center text-2xl font-bold tabular-nums @error('opening_amount') border-red-400 @enderror"
                                required
                                autofocus
                            >
                        </div>
                        @error('opening_amount')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-slate-500">Obligatorio cada día. Incluye el fondo para cambio.</p>
                    </div>

                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach([0, 500, 1000, 2000] as $preset)
                            <button type="button" class="opening-preset rounded-lg border border-slate-200 px-2 py-2 text-xs font-semibold text-slate-700 hover:border-indigo-400 hover:bg-indigo-50" data-amount="{{ $preset }}">
                                {{ number_format($preset, 0) }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex gap-2 pt-1">
                        <a href="{{ route('facturacion.pos') }}" class="btn-outline flex-1 justify-center text-sm">Ir al POS</a>
                        <button id="openBtn" type="submit" class="btn-primary flex-[2] justify-center text-sm">Abrir y vender</button>
                    </div>
                </div>
            </form>
        @else
            {{-- Caja abierta + cierre compacto --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-emerald-50 px-4 py-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-bold text-slate-900">Caja abierta</h1>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">ACTIVA</span>
                        </div>
                        <p class="truncate text-xs text-slate-500">
                            {{ $openSession->opened_at?->format('H:i') ?? '—' }}
                            @if($openSession->openedBy) · {{ $openSession->openedBy->name }} @endif
                            · {{ $now->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Fondo</p>
                        <p class="text-lg font-black tabular-nums text-slate-900">C$ {{ number_format((float) $openSession->opening_amount, 2) }}</p>
                    </div>
                </div>

                @if($summary)
                <div class="grid grid-cols-2 gap-px border-b border-slate-100 bg-slate-100 sm:grid-cols-4">
                    <div class="bg-white px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase text-slate-500">Ventas</p>
                        <p class="text-sm font-bold tabular-nums">{{ $summary['sales_count'] }}</p>
                    </div>
                    <div class="bg-white px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase text-slate-500">Efectivo</p>
                        <p class="text-sm font-bold tabular-nums">C$ {{ number_format($summary['cash_sales_total'], 2) }}</p>
                    </div>
                    <div class="bg-white px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase text-slate-500">Gastos</p>
                        <p class="text-sm font-bold tabular-nums text-red-600">C$ {{ number_format($summary['operational_expenses_cash_total'], 2) }}</p>
                    </div>
                    <div class="bg-white px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase text-slate-500">Esperado</p>
                        <p class="text-sm font-bold tabular-nums text-indigo-700">C$ {{ number_format($summary['expected_cash_total'], 2) }}</p>
                    </div>
                </div>
                @endif

                <div class="flex gap-2 p-3">
                    <a href="{{ route('facturacion.pos') }}" class="btn-outline flex-1 justify-center text-sm">Volver al POS</a>
                    <button id="showCountBtn" type="button" class="btn-primary flex-[2] justify-center text-sm">Cerrar caja</button>
                </div>
            </div>

            <form
                id="countForm"
                method="POST"
                action="{{ route('arqueo.run') }}"
                class="{{ request()->boolean('cerrar') ? '' : 'hidden' }} rounded-2xl border border-slate-200 bg-white shadow-sm"
                data-expected="{{ number_format((float) ($summary['expected_cash_total'] ?? 0), 2, '.', '') }}"
            >
                @csrf
                <input type="hidden" name="date" value="{{ \Carbon\Carbon::today()->toDateString() }}">
                <input type="hidden" name="caja_session_id" value="{{ $openSession->id }}">

                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-3 py-2">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Conteo y cierre</h2>
                        <p class="text-[11px] text-slate-500">+/− o teclado por denominación</p>
                    </div>
                    <button type="button" id="hideCountBtn" class="btn-outline px-2.5 py-1 text-xs">Ocultar</button>
                </div>

                <div class="grid gap-2 p-2.5 sm:grid-cols-[minmax(0,1fr)_150px]">
                    <div id="denoms" class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                        @foreach($denoms as $i => $denom)
                            <div class="denom-row rounded-md border border-slate-200 bg-slate-50/70 px-1.5 py-1">
                                <span class="text-[11px] font-bold tabular-nums text-slate-700">C${{ $denom }}</span>
                                <input type="hidden" name="physical_counts[{{ $i }}][amount]" value="{{ $denom }}">
                                <button type="button" class="qty-step rounded bg-white text-sm font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100" data-idx="{{ $i }}" data-delta="-1" aria-label="Restar">−</button>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    name="physical_counts[{{ $i }}][qty]"
                                    value="0"
                                    aria-label="Cantidad de C$ {{ $denom }}"
                                    class="qty-input border border-slate-300 bg-white font-bold tabular-nums text-slate-900"
                                    data-amount="{{ $denom }}"
                                >
                                <button type="button" class="qty-step rounded bg-white text-sm font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100" data-idx="{{ $i }}" data-delta="1" aria-label="Sumar">+</button>
                                <span class="denom-line-total text-right text-[10px] font-semibold tabular-nums text-slate-400">0</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-1.5">
                        <div id="numpad" class="grid grid-cols-3 gap-1">
                            @foreach(['7','8','9','4','5','6','1','2','3','0'] as $key)
                                <button type="button" tabindex="-1" class="pad-btn rounded-md bg-slate-100 py-1.5 text-sm font-semibold {{ $key === '0' ? 'col-span-2' : '' }}">{{ $key }}</button>
                            @endforeach
                            <button type="button" id="pad-back" tabindex="-1" class="rounded-md bg-red-100 py-1.5 text-sm font-semibold">⌫</button>
                        </div>

                        <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                            <div class="flex justify-between text-[11px] text-slate-500">
                                <span>Contado</span>
                                <span id="countedTotal" class="font-bold tabular-nums text-slate-900">C$ 0.00</span>
                            </div>
                            <div class="mt-0.5 flex justify-between text-[11px] text-slate-500">
                                <span>Esperado</span>
                                <span class="font-semibold tabular-nums">C$ {{ number_format((float) ($summary['expected_cash_total'] ?? 0), 2) }}</span>
                            </div>
                            <div class="mt-1.5 flex justify-between border-t border-slate-200 pt-1.5 text-xs">
                                <span class="font-semibold text-slate-700">Diferencia</span>
                                <span id="cashDifference" class="font-black tabular-nums text-slate-900">C$ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 border-t border-slate-100 p-2.5">
                    <button type="button" id="clearCountBtn" class="btn-outline flex-1 justify-center text-xs">Limpiar</button>
                    <button type="submit" class="btn-primary flex-[2] justify-center text-xs">Confirmar cierre</button>
                </div>
            </form>
        @endif

        <div id="overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-72 rounded-xl bg-white p-5 text-center shadow-xl">
                <div class="mb-2 text-2xl" aria-hidden="true">⏳</div>
                <div class="text-base font-semibold">Procesando...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const pad = (n) => (n < 10 ? '0' + n : n);
    const money = (value) => 'C$ ' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('clock');
        const date = document.getElementById('date');
        if (clock) clock.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        if (date) date.textContent = now.toLocaleDateString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    function showOverlay() {
        const overlay = document.getElementById('overlay');
        if (!overlay) return;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }

    const openingAmountInput = document.getElementById('opening_amount');
    document.querySelectorAll('.opening-preset').forEach((button) => {
        button.addEventListener('click', () => {
            if (!openingAmountInput) return;
            openingAmountInput.value = Number(button.dataset.amount || 0).toFixed(2);
            openingAmountInput.focus();
            openingAmountInput.select();
        });
    });

    if (openingAmountInput) {
        openingAmountInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9.,]/g, '');
        });
        openingAmountInput.addEventListener('blur', function () {
            if (this.value.trim() === '') return;
            const amount = Number(this.value.replace(/,/g, ''));
            if (Number.isFinite(amount) && amount >= 0) {
                this.value = amount.toFixed(2);
            }
        });
    }

    document.getElementById('openForm')?.addEventListener('submit', showOverlay);

    const countForm = document.getElementById('countForm');
    const showBtn = document.getElementById('showCountBtn');
    const hideBtn = document.getElementById('hideCountBtn');

    showBtn?.addEventListener('click', () => {
        countForm?.classList.remove('hidden');
        showBtn.classList.add('hidden');
        document.querySelector('.qty-input')?.focus();
        updateTotals();
    });

    hideBtn?.addEventListener('click', () => {
        countForm?.classList.add('hidden');
        showBtn?.classList.remove('hidden');
    });

    if (countForm && !countForm.classList.contains('hidden')) {
        showBtn?.classList.add('hidden');
    }

    if (!countForm) return;

    countForm.addEventListener('submit', showOverlay);

    let activeQty = null;
    let lastTouchedQty = null;

    function updateTotals() {
        let counted = 0;
        countForm.querySelectorAll('.qty-input').forEach((input) => {
            const qty = Number(input.value || 0);
            const amount = Number(input.dataset.amount || 0);
            const lineTotal = qty * amount;
            counted += lineTotal;

            const lineTotalEl = input.closest('div')?.querySelector('.denom-line-total');
            if (lineTotalEl) {
                lineTotalEl.textContent = qty > 0
                    ? Number(lineTotal).toLocaleString('en-US', { maximumFractionDigits: 0 })
                    : '0';
                lineTotalEl.classList.toggle('text-slate-700', qty > 0);
                lineTotalEl.classList.toggle('text-slate-400', qty <= 0);
            }

            input.classList.toggle('bg-indigo-50', qty > 0);
            input.classList.toggle('border-indigo-400', qty > 0);
        });

        const expected = Number(countForm.dataset.expected || 0);
        const difference = counted - expected;
        const countedEl = document.getElementById('countedTotal');
        const diffEl = document.getElementById('cashDifference');

        if (countedEl) countedEl.textContent = money(counted);
        if (diffEl) {
            diffEl.textContent = money(difference);
            diffEl.classList.toggle('text-red-600', difference < 0);
            diffEl.classList.toggle('text-emerald-600', difference >= 0);
            diffEl.classList.toggle('text-slate-900', false);
        }
    }

    countForm.addEventListener('keydown', (e) => {
        if (!activeQty) return;
        if (/^[0-9]$/.test(e.key)) {
            e.preventDefault();
            activeQty.value = activeQty.value === '0' ? e.key : activeQty.value + e.key;
            activeQty.dispatchEvent(new Event('input'));
            return;
        }
        if (e.key === 'Backspace') {
            e.preventDefault();
            activeQty.value = activeQty.value.slice(0, -1) || '0';
            activeQty.dispatchEvent(new Event('input'));
        }
    });

    countForm.querySelectorAll('.qty-input').forEach((input) => {
        input.addEventListener('focus', function () {
            activeQty = this;
            lastTouchedQty = this;
            this.select();
        });
        input.addEventListener('pointerdown', function () {
            activeQty = this;
            lastTouchedQty = this;
        });
        input.addEventListener('input', updateTotals);
        input.addEventListener('blur', () => { activeQty = null; });
    });

    countForm.querySelectorAll('.qty-step').forEach((button) => {
        button.addEventListener('click', () => {
            const idx = button.getAttribute('data-idx');
            const delta = Number(button.getAttribute('data-delta') || 0);
            const qty = countForm.querySelector(`input[name="physical_counts[${idx}][qty]"]`);
            if (!qty) return;
            qty.value = String(Math.max(0, Number(qty.value || 0) + delta));
            qty.focus();
            activeQty = qty;
            lastTouchedQty = qty;
            updateTotals();
        });
    });

    countForm.querySelectorAll('.pad-btn').forEach((button) => {
        button.addEventListener('click', () => {
            if (!activeQty) {
                const fallback = lastTouchedQty || (document.activeElement?.classList?.contains('qty-input') ? document.activeElement : null);
                if (fallback) {
                    fallback.focus();
                    activeQty = fallback;
                }
            }
            if (!activeQty) return;
            const val = button.textContent.trim();
            activeQty.value = val === '0'
                ? (activeQty.value === '0' ? '0' : activeQty.value + '0')
                : (activeQty.value === '0' ? val : activeQty.value + val);
            activeQty.dispatchEvent(new Event('input'));
        });
    });

    document.getElementById('pad-back')?.addEventListener('click', () => {
        if (!activeQty) return;
        activeQty.value = activeQty.value.slice(0, -1) || '0';
        activeQty.dispatchEvent(new Event('input'));
    });

    document.getElementById('clearCountBtn')?.addEventListener('click', () => {
        countForm.querySelectorAll('.qty-input').forEach((input) => {
            input.value = '0';
        });
        updateTotals();
    });

    updateTotals();
})();
</script>
@endpush
