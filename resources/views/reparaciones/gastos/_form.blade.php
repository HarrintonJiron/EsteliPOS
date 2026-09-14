@php
    $expense = $expense ?? null;
@endphp

@if($errors->any())
    <div class="card p-4 bg-red-50 border border-red-200 text-red-800 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('error'))
    <div class="card p-4 bg-red-50 border border-red-200 text-red-800 text-sm">
        {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div class="card p-5 space-y-4">
            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                Por defecto el gasto se paga con fondos externos y no altera la caja de ventas.
            </div>

            <div>
                <label class="block text-sm text-slate-600 mb-1">¿Para qué se sacó el dinero? *</label>
                <input type="text" name="description" value="{{ old('description', $expense?->description) }}" required class="input-field" placeholder="Ej: compra de materiales, pago de transporte, herramientas...">
            </div>

            <div>
                <label class="block text-sm text-slate-600 mb-1">Cantidad de dinero *</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense?->amount) }}" required class="input-field" placeholder="0.00">
            </div>

            <div>
                <label class="block text-sm text-slate-600 mb-1">Observaciones</label>
                <textarea name="notes" rows="4" class="input-field resize-none" placeholder="Detalle adicional opcional sobre en qué se invirtió">{{ old('notes', $expense?->notes) }}</textarea>
            </div>

            @if(! $expense)
                <input type="hidden" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}">
                <input type="hidden" name="payment_method" value="{{ old('payment_method', 'cash') }}">
                <input type="hidden" name="status" value="{{ old('status', \App\Models\OperationalExpense::STATUS_REGISTERED) }}">
                <label class="block text-sm text-slate-600">Origen del dinero</label>
                <select name="funding_source" class="select-field">
                    <option value="external" @selected(old('funding_source', 'external') === 'external')>Caja externa / fondos administrativos</option>
                    <option value="sales_cash" @selected(old('funding_source') === 'sales_cash')>Caja de ventas (reduce el arqueo)</option>
                </select>
                <input type="hidden" name="caja_session_id" value="{{ old('caja_session_id', $defaultCajaSession?->id) }}">
            @endif
        </div>
    </div>

    <div class="space-y-5">
        <div class="card p-5 space-y-4">
            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 space-y-2">
                <p class="font-semibold text-slate-800">Impacto automático</p>
                <p>El asiento contable siempre se genera.</p>
                <p>Solo se descuenta del arqueo al elegir <strong>Caja de ventas</strong>.</p>
            </div>

            @if($expense)
                <details class="rounded-xl border border-slate-200 p-4 text-sm text-slate-600">
                    <summary class="cursor-pointer font-semibold text-slate-800">Opciones avanzadas</summary>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Caja / Sesión</label>
                            <select name="caja_session_id" class="select-field">
                                <option value="">Selecciona una caja</option>
                                @foreach($cajaSessions as $session)
                                    <option value="{{ $session->id }}" {{ (string) old('caja_session_id', $expense?->caja_session_id ?? $defaultCajaSession?->id) === (string) $session->id ? 'selected' : '' }}>
                                        Caja #{{ $session->id }} · {{ $session->date->format('d/m/Y') }} · {{ $session->status === 'open' ? 'Abierta' : 'Cerrada' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Método de pago</label>
                            <select name="payment_method" class="select-field">
                                @foreach($paymentMethods as $value => $label)
                                    <option value="{{ $value }}" {{ old('payment_method', $expense?->payment_method ?? 'cash') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Origen del dinero</label>
                            <select name="funding_source" class="select-field">
                                <option value="external" @selected(old('funding_source', $expense?->funding_source ?? 'external') === 'external')>Caja externa / fondos administrativos</option>
                                <option value="sales_cash" @selected(old('funding_source', $expense?->funding_source) === 'sales_cash')>Caja de ventas (reduce arqueo)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Cuenta contable</label>
                            <select name="account_id" class="select-field">
                                <option value="">Usar cuenta predeterminada del taller</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}" {{ (string) old('account_id', $expense?->account_id) === (string) $account->id ? 'selected' : '' }}>
                                        {{ $account->code }} · {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Orden relacionada</label>
                            <select name="repair_order_id" class="select-field">
                                <option value="">Sin orden relacionada</option>
                                @foreach($repairOrders as $order)
                                    <option value="{{ $order->id }}" {{ (string) old('repair_order_id', $expense?->repair_order_id) === (string) $order->id ? 'selected' : '' }}>
                                        {{ $order->order_number }} · {{ $order->client_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </details>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('gastos.index') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">Guardar gasto</button>
        </div>
    </div>
</div>
