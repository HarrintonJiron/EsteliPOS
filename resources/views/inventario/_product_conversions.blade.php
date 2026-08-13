    {{-- Unidades: calculadora + tabla --}}
    @php
        $baseUnitLabel = $product->baseUnitLabel();
        $baseUnitName = $product->baseUnit?->name ?? $baseUnitLabel;
        $usedUnitIds = $product->unitConversions->pluck('unit_id')->map(fn ($id) => (int) $id)->all();
        $alternateUnits = ($allUnits ?? collect())->filter(
            fn ($u) => (int) $u->id !== (int) $product->base_unit_id && ! in_array((int) $u->id, $usedUnitIds, true)
        );
        $formatFactor = fn ($value) => rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.') ?: '0';

        $calculatorUnits = collect();
        if ($product->baseUnit) {
            $calculatorUnits->push([
                'id' => (int) $product->baseUnit->id,
                'abbr' => $product->baseUnit->abbreviation,
                'name' => $product->baseUnit->name,
                'factor' => 1.0,
            ]);
        }
        foreach ($product->unitConversions as $conv) {
            if (! $conv->unit) {
                continue;
            }
            $calculatorUnits->push([
                'id' => (int) $conv->unit->id,
                'abbr' => $conv->unit->abbreviation,
                'name' => $conv->unit->name,
                'factor' => (float) $conv->factor_to_base,
            ]);
        }
        $showConversions = $product->unitConversions->isNotEmpty()
            || $errors->has('unit_id')
            || $errors->has('equals_base_qty')
            || request()->boolean('conversiones');
    @endphp

    <details id="conversiones" class="card group scroll-mt-24 overflow-hidden p-0" @if($showConversions) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 [&::-webkit-details-marker]:hidden">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Unidades y conversiones</h2>
                <p class="text-sm text-slate-500">
                    Opcional · inventario en
                    <span class="font-semibold text-slate-700">{{ $baseUnitName }} ({{ $baseUnitLabel }})</span>
                    @if($product->unitConversions->isNotEmpty())
                        · {{ $product->unitConversions->count() }} equivalencia{{ $product->unitConversions->count() === 1 ? '' : 's' }}
                    @else
                        · ej. 1 carga = 2 qq
                    @endif
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('inventario.units.index') }}" class="btn-outline text-xs" onclick="event.stopPropagation()">Unidades</a>
                <span class="text-xs font-medium text-slate-500 group-open:hidden">Mostrar</span>
                <span class="hidden text-xs font-medium text-slate-500 group-open:inline">Ocultar</span>
            </div>
        </summary>

        <div class="space-y-4 border-t border-slate-100 px-5 py-4">
        @if ($errors->has('unit_id') || $errors->has('equals_base_qty'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('unit_id') ?: $errors->first('equals_base_qty') }}
            </div>
        @endif

        @if(! $product->base_unit_id)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Define la <strong>unidad base</strong> del producto (editar) para usar calculadora y tabla.
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- Calculadora --}}
                <div
                    class="card overflow-hidden p-0"
                    id="unitCalculator"
                    data-units='@json($calculatorUnits->values())'
                    data-base="{{ $baseUnitLabel }}"
                >
                    <div class="border-b border-slate-100 bg-gradient-to-br from-slate-800 to-slate-700 px-5 py-4 text-white">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-300">Calculadora</p>
                        <h3 class="mt-1 text-lg font-bold">Convertir unidades</h3>
                        <p class="mt-0.5 text-xs text-slate-300">Prueba cuánto es en otra unidad</p>
                    </div>

                    <div class="space-y-4 p-5">
                        @if($calculatorUnits->count() < 2)
                            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                Agrega al menos una fila en la tabla (ej. carga = 2 {{ $baseUnitLabel }}) para usar la calculadora.
                            </div>
                        @else
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500">Cantidad</label>
                                <input type="number" id="calcQty" value="1" min="0" step="0.0001" class="input-field text-center text-2xl font-bold tracking-tight">
                            </div>

                            <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-500">De</label>
                                    <select id="calcFrom" class="select-field">
                                        @foreach($calculatorUnits as $u)
                                            <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['abbr'] }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" id="calcSwap" class="mb-0.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-slate-600 shadow-sm hover:bg-slate-50" title="Intercambiar">
                                    ⇄
                                </button>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-500">A</label>
                                    <select id="calcTo" class="select-field">
                                        @foreach($calculatorUnits as $index => $u)
                                            <option value="{{ $u['id'] }}" @selected($index === 1)>{{ $u['name'] }} ({{ $u['abbr'] }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-indigo-50 px-4 py-5 text-center ring-1 ring-indigo-100">
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Resultado</p>
                                <p id="calcResult" class="mt-1 text-3xl font-bold text-indigo-800">—</p>
                                <p id="calcDetail" class="mt-1 text-xs text-indigo-600/80"></p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach([1, 2, 5, 10, 25] as $quick)
                                    <button type="button" data-calc-quick="{{ $quick }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        {{ $quick }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tabla de equivalencias --}}
                <div class="card overflow-hidden p-0">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tabla</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-800">Equivalencias</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Cuánto vale 1 unidad en {{ $baseUnitLabel }}</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">Unidad</th>
                                    <th class="px-4 py-2.5 text-center">=</th>
                                    <th class="px-4 py-2.5">En {{ $baseUnitLabel }}</th>
                                    <th class="px-4 py-2.5 text-right">Precio</th>
                                    <th class="px-3 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="bg-emerald-50/70">
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-slate-800">1 {{ $baseUnitLabel }}</span>
                                        <span class="mt-0.5 block text-xs text-emerald-700">Base del inventario</span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-400">=</td>
                                    <td class="px-4 py-3 font-mono text-lg font-bold text-slate-900">1</td>
                                    <td class="px-4 py-3 text-right text-slate-700">C$ {{ number_format((float) $product->sale_price, 2) }}</td>
                                    <td class="px-3 py-3"></td>
                                </tr>

                                @foreach($product->unitConversions as $conv)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <span class="font-semibold text-slate-800">1 {{ $conv->unit->abbreviation ?? '—' }}</span>
                                            <span class="mt-0.5 block text-xs text-slate-500">
                                                {{ $conv->unit->name ?? '' }}
                                                @if($conv->is_default_sale_unit)
                                                    · venta default
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-slate-400">=</td>
                                        <td class="px-4 py-3">
                                            <span class="font-mono text-lg font-bold text-slate-900">{{ $formatFactor($conv->factor_to_base) }}</span>
                                            <span class="text-slate-500"> {{ $baseUnitLabel }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{ $conv->sale_price !== null ? 'C$ '.number_format((float) $conv->sale_price, 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <form method="POST" action="{{ route('inventario.conversions.destroy', [$product->id, $conv->id]) }}" onsubmit="return confirm('¿Quitar esta equivalencia?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Quitar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('inventario.conversions.store', $product->id) }}" class="space-y-3 border-t border-slate-100 bg-slate-50 px-4 py-4">
                        @csrf
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agregar a la tabla</p>
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-semibold text-slate-700">1</span>
                            <select name="unit_id" id="tableAltUnit" class="select-field max-w-[11rem] py-2" required @disabled($alternateUnits->isEmpty())>
                                <option value="">unidad…</option>
                                @foreach($alternateUnits as $u)
                                    <option value="{{ $u->id }}" data-abbr="{{ $u->abbreviation }}" @selected(old('unit_id') == $u->id)>
                                        {{ $u->abbreviation }} — {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="font-semibold text-slate-700">=</span>
                            <input
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                name="equals_base_qty"
                                id="tableEqualsQty"
                                value="{{ old('equals_base_qty', '2') }}"
                                class="input-field w-24 py-2 text-center font-mono font-bold"
                                required
                                placeholder="2"
                                @disabled($alternateUnits->isEmpty())
                            >
                            <span class="font-semibold text-indigo-700">{{ $baseUnitLabel }}</span>
                        </div>
                        <p id="tablePreview" class="text-xs font-medium text-indigo-700">Ej: 1 carga = 2 {{ $baseUnitLabel }}</p>
                        <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                            <input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price') }}" class="input-field py-2" placeholder="Precio venta (opcional)" @disabled($alternateUnits->isEmpty())>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-600">
                                <input type="checkbox" name="is_default_sale_unit" value="1" class="rounded border-slate-300" @checked(old('is_default_sale_unit')) @disabled($alternateUnits->isEmpty())>
                                Default venta
                            </label>
                            <button type="submit" class="btn-primary whitespace-nowrap text-sm" @disabled($alternateUnits->isEmpty())>Guardar</button>
                        </div>
                        @if($alternateUnits->isEmpty())
                            <p class="text-xs text-amber-700">
                                @if($product->unitConversions->isNotEmpty())
                                    Ya están todas las unidades disponibles.
                                @endif
                                <a href="{{ route('inventario.units.index') }}" class="font-semibold underline">Crear unidad nueva</a>
                            </p>
                        @endif
                    </form>
                </div>
            </div>
        @endif
        </div>
    </details>

    @push('scripts')
    <script>
    (() => {
        const panel = document.getElementById('conversiones');
        if (window.location.hash === '#conversiones' && panel) {
            panel.open = true;
        }

        document.querySelectorAll('a[href="#conversiones"]').forEach((link) => {
            link.addEventListener('click', () => {
                if (panel) {
                    panel.open = true;
                }
            });
        });

        // Preview de fila nueva en la tabla
        const tableUnit = document.getElementById('tableAltUnit');
        const tableQty = document.getElementById('tableEqualsQty');
        const tablePreview = document.getElementById('tablePreview');
        const base = @json($baseUnitLabel);

        const updateTablePreview = () => {
            if (!tablePreview) return;
            const opt = tableUnit?.options[tableUnit.selectedIndex];
            const abbr = opt?.dataset?.abbr || 'unidad';
            const value = tableQty?.value || '?';
            tablePreview.textContent = tableUnit?.value
                ? `Se guardará: 1 ${abbr} = ${value} ${base}`
                : `Ej: 1 carga = 2 ${base}`;
        };
        tableUnit?.addEventListener('change', updateTablePreview);
        tableQty?.addEventListener('input', updateTablePreview);
        updateTablePreview();

        // Calculadora
        const box = document.getElementById('unitCalculator');
        if (!box) return;
        const units = JSON.parse(box.dataset.units || '[]');
        if (units.length < 2) return;

        const byId = Object.fromEntries(units.map((u) => [String(u.id), u]));
        const qtyInput = document.getElementById('calcQty');
        const fromSelect = document.getElementById('calcFrom');
        const toSelect = document.getElementById('calcTo');
        const resultEl = document.getElementById('calcResult');
        const detailEl = document.getElementById('calcDetail');
        const swapBtn = document.getElementById('calcSwap');

        const format = (n) => {
            if (!Number.isFinite(n)) return '—';
            return n.toLocaleString('es-NI', { maximumFractionDigits: 4 });
        };

        const calculate = () => {
            const from = byId[fromSelect.value];
            const to = byId[toSelect.value];
            const qty = parseFloat(qtyInput.value);
            if (!from || !to || !Number.isFinite(qty)) {
                resultEl.textContent = '—';
                detailEl.textContent = '';
                return;
            }
            if (to.factor === 0) {
                resultEl.textContent = '—';
                detailEl.textContent = 'Factor inválido';
                return;
            }
            const result = (qty * from.factor) / to.factor;
            resultEl.textContent = `${format(result)} ${to.abbr}`;
            detailEl.textContent = `${format(qty)} ${from.abbr} = ${format(result)} ${to.abbr}`;
        };

        qtyInput?.addEventListener('input', calculate);
        fromSelect?.addEventListener('change', calculate);
        toSelect?.addEventListener('change', calculate);
        swapBtn?.addEventListener('click', () => {
            const a = fromSelect.value;
            fromSelect.value = toSelect.value;
            toSelect.value = a;
            calculate();
        });
        document.querySelectorAll('[data-calc-quick]').forEach((btn) => {
            btn.addEventListener('click', () => {
                qtyInput.value = btn.dataset.calcQuick;
                calculate();
            });
        });
        calculate();
    })();
    </script>
    @endpush

