{{--
  Price Calculator Partial
  Required variables:
    $purchaseInputId  — JS id of the purchase_price <input>
    $saleInputId      — JS id of the sale_price <input>
--}}
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-3" id="priceCalcWidget_{{ $saleInputId }}">
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-semibold text-amber-800">Calculadora de Precio de Venta</span>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs text-amber-700 font-medium mb-1">Método</label>
            <select id="calcMethod_{{ $saleInputId }}" class="w-full px-3 py-2 text-sm border border-amber-300 rounded-lg bg-white focus:outline-none focus:border-amber-500">
                <option value="manual">Manual (sin cálculo automático)</option>
                <option value="markup" selected>Markup % sobre costo &nbsp;·&nbsp; PV = PC × (1 + %)</option>
                <option value="margin">Margen de utilidad % &nbsp;·&nbsp; PV = PC ÷ (1 − %)</option>
                <option value="multiplier">Multiplicador (factor) &nbsp;·&nbsp; PV = PC × factor</option>
                <option value="fixed">Monto fijo adicional (C$) &nbsp;·&nbsp; PV = PC + C$</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-amber-700 font-medium mb-1" id="calcRateLabel_{{ $saleInputId }}">
                Markup (%)
            </label>
            <input type="number" id="calcRate_{{ $saleInputId }}"
                value="30" step="0.01" min="0"
                class="w-full px-3 py-2 text-sm border border-amber-300 rounded-lg bg-white focus:outline-none focus:border-amber-500 font-semibold text-amber-900">
        </div>
    </div>

    {{-- Live preview panel --}}
    <div id="calcPreview_{{ $saleInputId }}"
         class="hidden bg-white border border-amber-200 rounded-xl p-3 grid grid-cols-3 gap-3 text-center">
        <div>
            <p class="text-xs text-slate-500 mb-0.5">Precio venta</p>
            <p class="font-bold text-indigo-700" id="previewSale_{{ $saleInputId }}">—</p>
        </div>
        <div>
            <p class="text-xs text-slate-500 mb-0.5">Ganancia neta</p>
            <p class="font-bold text-emerald-600" id="previewProfit_{{ $saleInputId }}">—</p>
        </div>
        <div>
            <p class="text-xs text-slate-500 mb-0.5">Margen real</p>
            <p class="font-bold text-amber-700" id="previewMargin_{{ $saleInputId }}">—</p>
        </div>
    </div>

    {{-- Presets quick buttons --}}
    <div id="calcPresets_{{ $saleInputId }}" class="flex flex-wrap gap-1.5">
        <span class="text-xs text-amber-600 self-center font-medium">Rápidos:</span>
        @foreach([10, 15, 20, 25, 30, 40, 50, 100] as $pct)
        <button type="button"
            onclick="applyPreset_{{ $saleInputId }}({{ $pct }})"
            class="px-2 py-0.5 text-xs bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold rounded-lg border border-amber-200 transition">
            {{ $pct }}%
        </button>
        @endforeach
    </div>
</div>

<script>
(function () {
    const SALE_ID     = '{{ $saleInputId }}';
    const PURCH_ID    = '{{ $purchaseInputId }}';

    const methodSel   = () => document.getElementById('calcMethod_'   + SALE_ID);
    const rateSel     = () => document.getElementById('calcRate_'     + SALE_ID);
    const rateLabel   = () => document.getElementById('calcRateLabel_'+ SALE_ID);
    const previewBox  = () => document.getElementById('calcPreview_'  + SALE_ID);
    const presetsBox  = () => document.getElementById('calcPresets_'  + SALE_ID);
    const prevSale    = () => document.getElementById('previewSale_'  + SALE_ID);
    const prevProfit  = () => document.getElementById('previewProfit_'+ SALE_ID);
    const prevMargin  = () => document.getElementById('previewMargin_'+ SALE_ID);
    const saleInput   = () => document.getElementById(SALE_ID);
    const purchInput  = () => document.getElementById(PURCH_ID);

    const fmt  = v  => 'C$ ' + parseFloat(v || 0).toFixed(2);

    const LABELS = {
        manual:     'Sin cálculo automático',
        markup:     'Markup (%)',
        margin:     'Margen de utilidad (%)',
        multiplier: 'Multiplicador (factor)',
        fixed:      'Monto fijo adicional (C$)',
    };

    const DEFAULT_RATES = { manual: 0, markup: 30, margin: 30, multiplier: 1.5, fixed: 50 };

    function calcSale(method, cost, rate) {
        if (cost <= 0 || method === 'manual') return null;
        switch (method) {
            case 'markup':
                return cost * (1 + rate / 100);
            case 'margin':
                if (rate >= 100) return null;
                return cost / (1 - rate / 100);
            case 'multiplier':
                if (rate <= 0) return null;
                return cost * rate;
            case 'fixed':
                return cost + rate;
        }
        return null;
    }

    function recalc() {
        const method = methodSel()?.value;
        const cost   = parseFloat(purchInput()?.value || 0);
        const rate   = parseFloat(rateSel()?.value   || 0);
        const sale   = calcSale(method, cost, rate);

        rateLabel().textContent = LABELS[method] ?? 'Valor';

        const isManual = (method === 'manual');
        presetsBox().style.display = isManual ? 'none' : 'flex';
        rateSel().disabled        = isManual;

        if (sale !== null && sale > 0) {
            previewBox().classList.remove('hidden');
            const profit     = sale - cost;
            const marginReal = sale > 0 ? (profit / sale * 100) : 0;

            prevSale().textContent   = fmt(sale);
            prevProfit().textContent = fmt(profit);
            prevMargin().textContent = marginReal.toFixed(1) + '%';

            if (saleInput()) {
                saleInput().value = sale.toFixed(2);
            }
        } else {
            previewBox().classList.add('hidden');
        }
    }

    window['applyPreset_' + SALE_ID] = function(pct) {
        if (rateSel()) rateSel().value = pct;
        recalc();
    };

    // Wire events after DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        const m = methodSel();
        const r = rateSel();
        const p = purchInput();
        const s = saleInput();

        if (m) m.addEventListener('change', function() {
            // reset rate to sensible default for the method
            const def = DEFAULT_RATES[this.value];
            if (def !== undefined && r) r.value = def;
            recalc();
        });
        if (r) r.addEventListener('input', recalc);
        if (p) p.addEventListener('input', recalc);

        // Allow manual override of sale price → recalc back to show actual margin
        if (s) s.addEventListener('input', function() {
            const cost = parseFloat(p?.value || 0);
            const sale = parseFloat(this.value || 0);
            if (cost > 0 && sale > 0) {
                const profit     = sale - cost;
                const marginReal = profit / sale * 100;
                previewBox().classList.remove('hidden');
                prevSale().textContent   = fmt(sale);
                prevProfit().textContent = fmt(profit);
                prevMargin().textContent = marginReal.toFixed(1) + '%';
            }
        });

        recalc(); // initial calc on page load
    });
})();
</script>
