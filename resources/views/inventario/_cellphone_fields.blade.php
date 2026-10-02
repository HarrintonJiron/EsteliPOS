@php
    $editingProduct = $product ?? null;
    $selectedCondition = old('condition', $editingProduct ? ($editingProduct->condition ?? '') : 'new');
    $conditionOptions = [
        '' => ['No aplica', 'Artículo general', 'bg-slate-300'],
        'new' => ['Nuevo', 'Sellado de fábrica', 'bg-emerald-500'],
        'used' => ['Seminuevo', 'Equipo revisado', 'bg-amber-500'],
        'open_box' => ['Open box', 'Caja abierta', 'bg-sky-500'],
    ];
@endphp
<section class="overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50/50 shadow-sm">
    <div class="flex items-center gap-4 border-b border-indigo-100 bg-white/80 p-5">
        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="2"/><path d="M11 18h2" stroke-width="2" stroke-linecap="round"/></svg></span>
        <div><h2 class="text-lg font-bold text-slate-900">Ficha comercial del equipo</h2><p class="text-sm text-slate-500">Identifica rápidamente el estado, variante y precios. En artículos generales puedes dejarla vacía.</p></div>
    </div>
    <div class="space-y-6 p-5">
        <fieldset>
            <legend class="text-sm font-bold text-slate-800">Estado del artículo</legend>
            <p id="condition-help" class="mb-3 mt-1 text-xs text-slate-500">La opción marcada se guardará en la ficha comercial del producto.</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($conditionOptions as $value => $option)
                    <label data-condition-option="{{ $value ?: 'none' }}" class="relative flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-400 has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50 has-[:checked]:shadow-md has-[:checked]:ring-2 has-[:checked]:ring-indigo-200">
                        <input type="radio" name="condition" value="{{ $value }}" class="peer mt-0.5 h-5 w-5 shrink-0 accent-indigo-600" aria-describedby="condition-help" @checked($selectedCondition === $value)>
                        <span class="min-w-0 pr-5">
                            <strong class="block text-sm text-slate-900">{{ $option[0] }}</strong>
                            <small class="text-slate-500">{{ $option[1] }}</small>
                        </span>
                        <span class="absolute right-3 top-3 hidden h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white peer-checked:inline-flex" aria-hidden="true">✓</span>
                    </label>
                @endforeach
            </div>
            @error('condition')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </fieldset>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-semibold text-slate-700">Marca<input name="brand" list="phone-brands" value="{{ old('brand',$editingProduct?->brand) }}" class="input-field mt-2" placeholder="Apple, Samsung…"><datalist id="phone-brands"><option value="Apple"><option value="Samsung"><option value="Xiaomi"><option value="Motorola"><option value="Honor"><option value="Google"><option value="OnePlus"></datalist></label>
            <label class="text-sm font-semibold text-slate-700">Modelo<input name="model" value="{{ old('model',$editingProduct?->model) }}" class="input-field mt-2" placeholder="iPhone 15 Pro"></label>
            <label class="text-sm font-semibold text-slate-700">Color<input name="color" list="phone-colors" value="{{ old('color',$editingProduct?->color) }}" class="input-field mt-2" placeholder="Negro, azul…"><datalist id="phone-colors"><option value="Negro"><option value="Blanco"><option value="Azul"><option value="Dorado"><option value="Plateado"><option value="Verde"><option value="Morado"></datalist></label>
            <label class="text-sm font-semibold text-slate-700">Batería (%)<div class="relative mt-2"><input type="number" name="battery_percentage" value="{{ old('battery_percentage',$editingProduct?->battery_percentage) }}" min="0" max="100" step="1" class="input-field pr-8" placeholder="Ej: 87"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">%</span></div><span class="mt-1 block text-xs font-normal text-slate-500">Solo para equipos usados/open box.</span></label>
            <label class="text-sm font-semibold text-slate-700">IMEI o serial<input name="imei" value="{{ old('imei',$editingProduct?->imei) }}" class="input-field mt-2 font-mono" maxlength="30" inputmode="numeric" placeholder="Escanea o escribe el IMEI"><span class="mt-1 block text-xs font-normal text-slate-500">Debe ser único en inventario.</span></label>
        </div>
        <div><div class="mb-3"><h3 class="text-sm font-bold text-slate-700">Precios adicionales</h3><p class="text-xs text-slate-500">El precio de venta principal se mantiene en la sección general del producto.</p></div><div class="grid gap-4 md:grid-cols-2">
            <label class="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700"><span class="flex items-center justify-between"><span>Precio mayorista</span><span class="rounded-full bg-violet-50 px-2 py-1 text-xs text-violet-700">Por volumen</span></span><div class="relative mt-3"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">{{ $currencySymbol }}</span><input type="text" inputmode="decimal" name="wholesale_price" value="{{ old('wholesale_price') }}" class="input-field money-prefix-input" placeholder="0.00"></div></label>
            <label class="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700"><span class="flex items-center justify-between"><span>Precio especial</span><span class="rounded-full bg-amber-50 px-2 py-1 text-xs text-amber-700">Cliente preferente</span></span><div class="relative mt-3"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">{{ $currencySymbol }}</span><input type="text" inputmode="decimal" name="special_price" value="{{ old('special_price') }}" class="input-field money-prefix-input" placeholder="0.00"></div></label>
        </div></div>
    </div>
</section>
