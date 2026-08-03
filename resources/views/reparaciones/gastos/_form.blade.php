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
            @if($defaultCajaSession ?? false)
                <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                    Se registrará en <strong>Caja #{{ $defaultCajaSession->id }}</strong> abierta el {{ $defaultCajaSession->date->format('d/m/Y') }}.
                </div>
            @else
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    No hay una caja abierta para hoy. Abre una caja en Arqueo para poder registrar el gasto.
                </div>
            @endif

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
                <input type="hidden" name="caja_session_id" value="{{ old('caja_session_id', $defaultCajaSession?->id) }}">
            @endif
        </div>
    </div>

    <div class="space-y-5">
        <div class="card p-5 space-y-4">
            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 space-y-2">
                <p class="font-semibold text-slate-800">Impacto automático</p>
                <p>Al guardar, el sistema lo registra como <strong>egreso en efectivo</strong> en la caja abierta del día.</p>
                <p>También genera el asiento contable y descuenta el monto del arqueo.</p>
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
            <a href="{{ route('reparaciones.gastos.index') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary" {{ ($defaultCajaSession ?? null) ? '' : 'disabled' }}>Guardar gasto</button>
        </div>
    </div>
</div>