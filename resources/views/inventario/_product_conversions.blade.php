    @php
        $baseUnitLabel = $product->baseUnitLabel();
        $baseUnitName = $product->baseUnit?->name ?? $baseUnitLabel;
        $usedUnitIds = $product->unitConversions->pluck('unit_id')->map(fn ($id) => (int) $id)->all();
        $alternateUnits = ($allUnits ?? collect())->filter(
            fn ($u) => (int) $u->id !== (int) $product->base_unit_id && ! in_array((int) $u->id, $usedUnitIds, true)
        );
        $formatFactor = fn ($value) => rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.') ?: '0';
        $unitsByAbbr = collect($allUnits ?? [])->keyBy(fn ($unit) => $unit->abbreviation);
        $availableAbbr = $alternateUnits->pluck('abbreviation')->all();
        $presets = [];
        if (in_array('ristra', $availableAbbr, true) && $product->base_unit_id) {
            $presets[] = ['label' => 'Ristra de 3', 'unit_id' => $unitsByAbbr->get('ristra')?->id, 'qty' => 3, 'equals_unit_id' => $product->base_unit_id, 'default' => true];
        }
        if (in_array('caja', $availableAbbr, true) && $product->base_unit_id) {
            $ristraConv = $product->unitConversions->first(fn ($conv) => $conv->unit?->abbreviation === 'ristra');
            $presets[] = $ristraConv
                ? ['label' => 'Caja de 12 ristras', 'unit_id' => $unitsByAbbr->get('caja')?->id, 'qty' => 12, 'equals_unit_id' => $ristraConv->unit_id, 'default' => false]
                : ['label' => 'Caja de 24', 'unit_id' => $unitsByAbbr->get('caja')?->id, 'qty' => 24, 'equals_unit_id' => $product->base_unit_id, 'default' => false];
        }
        if (in_array('doc', $availableAbbr, true) && $product->base_unit_id) {
            $presets[] = ['label' => 'Docena', 'unit_id' => $unitsByAbbr->get('doc')?->id, 'qty' => 12, 'equals_unit_id' => $product->base_unit_id, 'default' => false];
        }
        $presets = array_values(array_filter($presets, fn ($preset) => $preset['unit_id']));
        $openModal = $errors->has('unit_id') || $errors->has('equals_base_qty') || $errors->has('equals_unit_id')
            || request()->boolean('conversiones');
    @endphp

    <span id="conversiones" class="sr-only">Unidades y conversiones Opcional</span>

    <div
        id="presentationModal"
        class="{{ $openModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="presentationModalTitle"
    >
        <div class="w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 id="presentationModalTitle" class="text-lg font-semibold text-slate-900">Compra y venta por presentación</h2>
                    <p class="text-xs text-slate-500">Cómo se vende y cómo se compra · inventario controlado en {{ $baseUnitLabel }}</p>
                </div>
                <button type="button" class="text-2xl leading-none text-slate-400 hover:text-slate-700" data-close-presentations aria-label="Cerrar">×</button>
            </div>

            <div class="space-y-4 px-5 py-4">
                @if ($errors->has('unit_id') || $errors->has('equals_base_qty') || $errors->has('equals_unit_id'))
                    <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $errors->first('unit_id') ?: $errors->first('equals_unit_id') ?: $errors->first('equals_base_qty') }}
                    </div>
                @endif

                @if(! $product->base_unit_id)
                    <p class="text-sm text-amber-800">Elige la unidad en Editar y vuelve aquí.</p>
                @else
                    <div class="grid gap-2 sm:grid-cols-3" aria-label="Flujo de configuración">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-700">1 · Inventario</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-950">Se controla en {{ $baseUnitName }}</p>
                        </div>
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-blue-700">2 · Compra</p>
                            <p class="mt-1 text-sm font-semibold text-blue-950">Caja, saco o quintal</p>
                        </div>
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-indigo-700">3 · Venta</p>
                            <p class="mt-1 text-sm font-semibold text-indigo-950">Unidad, ristra o caja</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">1 {{ $baseUnitLabel }}</span>
                        @foreach($product->unitConversions as $conv)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                1 {{ $conv->unit->abbreviation ?? '—' }} =
                                @if($conv->equals_quantity && $conv->equalsUnit)
                                    {{ $formatFactor($conv->equals_quantity) }} {{ $conv->equalsUnit->abbreviation }}
                                    @if((int) $conv->equals_unit_id !== (int) $product->base_unit_id)
                                        · {{ $formatFactor($conv->factor_to_base) }} {{ $baseUnitLabel }}
                                    @endif
                                @else
                                    {{ $formatFactor($conv->factor_to_base) }} {{ $baseUnitLabel }}
                                @endif
                            </span>
                        @endforeach
                    </div>

                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between gap-2 px-3 py-2.5 text-sm">
                            <div>
                                <span class="font-semibold text-slate-800">1 {{ $baseUnitLabel }}</span>
                                @if(! $product->unitConversions->contains('is_default_sale_unit', true))
                                    <span class="ml-1 text-[10px] font-semibold uppercase text-indigo-600">Predeterminada</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-slate-600">C$ {{ number_format((float) $product->sale_price, 2) }}</span>
                                @if($product->unitConversions->contains('is_default_sale_unit', true))
                                    <form method="POST" action="{{ route('inventario.conversions.default', $product->id) }}">
                                        @csrf
                                        <input type="hidden" name="unit_id" value="{{ $product->base_unit_id }}">
                                        <button type="submit" class="text-xs text-indigo-600 hover:underline">Usar al vender</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @foreach($product->unitConversions as $conv)
                            <div class="flex items-center justify-between gap-2 px-3 py-2.5 text-sm">
                                <div>
                                    <span class="font-semibold text-slate-800">1 {{ $conv->unit->abbreviation ?? '—' }}</span>
                                    <span class="text-slate-500"> = {{ $formatFactor($conv->factor_to_base) }} {{ $baseUnitLabel }}</span>
                                    @if($conv->use_for_purchase)<span class="ml-1 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700">Compra</span>@endif
                                    @if($conv->use_for_sale)<span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">Venta</span>@endif
                                    @if($conv->is_default_sale_unit)
                                        <span class="ml-1 text-[10px] font-semibold uppercase text-indigo-600">Predeterminada</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-700">
                                        C$ {{ number_format((float) ($conv->sale_price ?? $product->sale_price * $conv->factor_to_base), 2) }}
                                    </span>
                                    @if(! $conv->is_default_sale_unit)
                                        <form method="POST" action="{{ route('inventario.conversions.default', $product->id) }}">
                                            @csrf
                                            <input type="hidden" name="unit_id" value="{{ $conv->unit_id }}">
                                            <button type="submit" class="text-xs text-indigo-600 hover:underline">Usar al vender</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('inventario.conversions.destroy', [$product->id, $conv->id]) }}" onsubmit="return confirm('¿Quitar?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">Quitar</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('inventario.conversions.store', $product->id) }}" class="space-y-3" id="presentationForm">
                        @csrf
                        <input type="hidden" name="usage_options" value="1">
                        <div>
                            <p class="mb-2 text-sm font-semibold text-slate-900">Agregar otra presentación</p>
                            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm">
                            <span class="font-semibold text-slate-800">1</span>
                            <select name="unit_id" id="tableAltUnit" class="select-field py-2" required @disabled($alternateUnits->isEmpty())>
                                <option value="">…</option>
                                @foreach($alternateUnits as $u)
                                    <option value="{{ $u->id }}" data-name="{{ $u->name }}" @selected(old('unit_id') == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <span class="font-semibold text-slate-800">contiene</span>
                            <input type="number" step="0.000001" min="0.000001" name="equals_base_qty" id="tableEqualsQty" value="{{ old('equals_base_qty', '3') }}" class="input-field w-20 py-2 text-center font-mono font-bold" required @disabled($alternateUnits->isEmpty())>
                            <select name="equals_unit_id" id="tableEqualsUnit" class="select-field py-2" @disabled($alternateUnits->isEmpty())>
                                @if($product->baseUnit)
                                    <option value="{{ $product->baseUnit->id }}" data-name="{{ $product->baseUnit->name }}" data-factor="1" @selected((int) old('equals_unit_id', $product->base_unit_id) === (int) $product->base_unit_id)>{{ $product->baseUnit->name }}</option>
                                @endif
                                @foreach($product->unitConversions as $conv)
                                    @if($conv->unit)
                                        <option value="{{ $conv->unit->id }}" data-name="{{ $conv->unit->name }}" data-factor="{{ (float) $conv->factor_to_base }}" @selected((int) old('equals_unit_id') === (int) $conv->unit->id)>{{ $conv->unit->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            </div>
                        </div>

                        @if($presets !== [])
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($presets as $preset)
                                    <button type="button" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-700 hover:border-indigo-400 hover:bg-indigo-50" data-unit-id="{{ $preset['unit_id'] }}" data-qty="{{ $preset['qty'] }}" data-equals-unit-id="{{ $preset['equals_unit_id'] }}" data-default="{{ $preset['default'] ? '1' : '0' }}">
                                        {{ $preset['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="presentationSalePrice" class="mb-1 block text-xs font-medium text-slate-600">Precio de venta de esta presentación</label>
                                <input id="presentationSalePrice" type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price') }}" class="input-field py-2" placeholder="Automático si se deja vacío" @disabled($alternateUnits->isEmpty())>
                            </div>
                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                <input type="checkbox" name="is_default_sale_unit" value="1" class="rounded border-slate-300" @checked(old('is_default_sale_unit')) @disabled($alternateUnits->isEmpty())>
                                Predeterminada al vender
                            </label>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="mb-2 text-xs font-semibold text-slate-700">¿Dónde se utiliza esta presentación?</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-600">
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" name="use_for_purchase" value="1" class="rounded border-slate-300" @checked(old('use_for_purchase', true))>
                                    Al comprar
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" name="use_for_sale" value="1" class="rounded border-slate-300" @checked(old('use_for_sale', true))>
                                    Al vender
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" name="is_default_purchase_unit" value="1" class="rounded border-slate-300" @checked(old('is_default_purchase_unit'))>
                                    Predeterminada al comprar
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" name="allow_fraction" value="1" class="rounded border-slate-300" @checked(old('allow_fraction'))>
                                    Permitir fracciones
                                </label>
                            </div>
                            <label for="presentationBarcode" class="mt-3 block text-xs font-medium text-slate-600">Código de barras de esta presentación (opcional)</label>
                            <input id="presentationBarcode" name="barcode" value="{{ old('barcode') }}" class="input-field mt-1 py-2" placeholder="Escanee o escriba el código">
                            @error('barcode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div id="presentationPreview" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-950" aria-live="polite">
                            Seleccione una presentación para ver cómo afectará el inventario.
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn-primary px-5 py-2 text-sm" @disabled($alternateUnits->isEmpty())>Guardar presentación</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (() => {
        const modal = document.getElementById('presentationModal');
        const open = () => {
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
        const close = () => {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        document.querySelectorAll('[data-open-presentations], a[href="#conversiones"]').forEach((el) => {
            el.addEventListener('click', (event) => {
                event.preventDefault();
                open();
            });
        });
        document.querySelectorAll('[data-close-presentations]').forEach((el) => el.addEventListener('click', close));
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });
        if (window.location.hash === '#conversiones') {
            open();
        }

        const tableUnit = document.getElementById('tableAltUnit');
        const tableQty = document.getElementById('tableEqualsQty');
        const tableEqualsUnit = document.getElementById('tableEqualsUnit');
        const defaultCheck = document.querySelector('#presentationForm [name="is_default_sale_unit"]');
        const preview = document.getElementById('presentationPreview');
        const purchaseCheck = document.querySelector('#presentationForm [name="use_for_purchase"]');
        const saleCheck = document.querySelector('#presentationForm [name="use_for_sale"]');

        const updatePreview = () => {
            if (!preview || !tableUnit || !tableQty || !tableEqualsUnit) return;
            const presentation = tableUnit.selectedOptions[0]?.dataset.name;
            const containedUnit = tableEqualsUnit.selectedOptions[0]?.dataset.name || '{{ $baseUnitName }}';
            const containedQty = parseFloat(tableQty.value);
            const containedFactor = parseFloat(tableEqualsUnit.selectedOptions[0]?.dataset.factor || '1');
            if (!presentation || !Number.isFinite(containedQty) || containedQty <= 0) {
                preview.textContent = 'Seleccione una presentación para ver cómo afectará el inventario.';
                return;
            }
            const baseQty = containedQty * containedFactor;
            const formatted = baseQty.toLocaleString('es-NI', { maximumFractionDigits: 4 });
            const actions = [purchaseCheck?.checked ? 'comprar' : null, saleCheck?.checked ? 'vender' : null].filter(Boolean).join(' y ');
            preview.textContent = `Comprobación: 1 ${presentation} contiene ${containedQty} ${containedUnit} y mueve ${formatted} {{ $baseUnitLabel }} del inventario${actions ? ` al ${actions}` : ''}.`;
        };

        document.querySelectorAll('#presentationForm [data-unit-id]').forEach((button) => {
            button.addEventListener('click', () => {
                if (tableUnit) tableUnit.value = button.dataset.unitId || '';
                if (tableQty) tableQty.value = button.dataset.qty || '';
                if (tableEqualsUnit) tableEqualsUnit.value = button.dataset.equalsUnitId || '';
                if (defaultCheck) defaultCheck.checked = button.dataset.default === '1';
                updatePreview();
            });
        });
        [tableUnit, tableQty, tableEqualsUnit, purchaseCheck, saleCheck].forEach((field) => {
            field?.addEventListener('input', updatePreview);
            field?.addEventListener('change', updatePreview);
        });
        updatePreview();
    })();
    </script>
    @endpush
