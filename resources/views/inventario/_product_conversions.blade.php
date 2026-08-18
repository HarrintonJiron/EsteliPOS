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
        <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 id="presentationModalTitle" class="text-lg font-semibold text-slate-900">Cómo se vende</h2>
                    <p class="text-xs text-slate-500">1 producto · stock en {{ $baseUnitLabel }}</p>
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
                    <div class="flex flex-wrap gap-1.5">
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">1 {{ $baseUnitLabel }}</span>
                        @foreach($product->unitConversions as $conv)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                1 {{ $conv->unit->abbreviation ?? '—' }} = {{ $formatFactor($conv->factor_to_base) }} {{ $baseUnitLabel }}
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
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-semibold text-slate-800">Una</span>
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
                                    <option value="{{ $product->baseUnit->id }}" data-name="{{ $product->baseUnit->name }}" @selected((int) old('equals_unit_id', $product->base_unit_id) === (int) $product->base_unit_id)>{{ $product->baseUnit->name }}</option>
                                @endif
                                @foreach($product->unitConversions as $conv)
                                    @if($conv->unit)
                                        <option value="{{ $conv->unit->id }}" data-name="{{ $conv->unit->name }}" @selected((int) old('equals_unit_id') === (int) $conv->unit->id)>{{ $conv->unit->name }}</option>
                                    @endif
                                @endforeach
                            </select>
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

                        <div class="flex flex-wrap items-center gap-2">
                            <input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price') }}" class="input-field flex-1 py-2" placeholder="Precio (opcional)" @disabled($alternateUnits->isEmpty())>
                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                <input type="checkbox" name="is_default_sale_unit" value="1" class="rounded border-slate-300" @checked(old('is_default_sale_unit')) @disabled($alternateUnits->isEmpty())>
                                Predeterminada al vender
                            </label>
                            <button type="submit" class="btn-primary text-sm" @disabled($alternateUnits->isEmpty())>Guardar</button>
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

        document.querySelectorAll('#presentationForm [data-unit-id]').forEach((button) => {
            button.addEventListener('click', () => {
                if (tableUnit) tableUnit.value = button.dataset.unitId || '';
                if (tableQty) tableQty.value = button.dataset.qty || '';
                if (tableEqualsUnit) tableEqualsUnit.value = button.dataset.equalsUnitId || '';
                if (defaultCheck) defaultCheck.checked = button.dataset.default === '1';
            });
        });
    })();
    </script>
    @endpush
