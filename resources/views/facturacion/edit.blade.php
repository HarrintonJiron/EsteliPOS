@extends('layouts.app')

@section('title', 'Editar Factura #' . $sale->id)

@section('content')

<div id="invoiceApp" class="space-y-4" data-products='@json($products)' data-sale='@json($sale)' data-clients='@json($clients)'>

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-700">Editar Factura #{{ $sale->id }}</h2>
            <p class="text-sm text-gray-500">Modificación de venta de productos agrícolas</p>
        </div>

        <span class="bg-yellow-100 text-yellow-700 px-4 py-2 rounded-full text-sm">
            Editando factura
        </span>
    </div>

    @if($errors->any())
        <div class="bg-red-100 text-red-800 px-4 py-3 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('facturacion.update', $sale->id) }}" method="POST" id="invoiceForm">
        @csrf
        @method('PUT')

        {{-- Datos principales --}}
        <div class="bg-white p-4 rounded-xl shadow space-y-4">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div>
                    <label class="text-sm text-gray-600">Cliente</label>
                    <select name="client_id" required id="clientSelect" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                        <option value="">-- seleccionar --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ $sale->client_id == $c->id ? 'selected' : '' }}>{{ $c->code ? ($c->code . ' - ') : '' }}{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-600">No. Factura</label>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number', $sale->invoice_number) }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-gray-50" readonly />
                    <p class="text-xs text-gray-400 mt-1">Consecutivo automático</p>
                </div>

                <div>
                    <label class="text-sm text-gray-600">Fecha</label>
                    <input type="date" name="date" value="{{ $sale->date ? $sale->date->format('Y-m-d') : date('Y-m-d') }}" required
                        class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Método de Pago</label>
                    <select name="payment_type" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                        <option value="cash" {{ $sale->payment_type == 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="transfer" {{ $sale->payment_type == 'transfer' ? 'selected' : '' }}>Transferencia</option>
                        <option value="credit" {{ $sale->payment_type == 'credit' ? 'selected' : '' }}>Crédito</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-600">IVA</label>
                    <select name="tax_included" id="taxIncluded" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                        <option value="0" {{ !$sale->tax_included ? 'selected' : '' }}>Sin IVA (se suma 15%)</option>
                        <option value="1" {{ $sale->tax_included ? 'selected' : '' }}>Con IVA (incluido en precios)</option>
                    </select>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Nombre a facturar</label>
                    <input type="text" name="billing_name" id="billingName" required value="{{ old('billing_name', $sale->billing_name ?? $sale->client->name ?? '') }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>

                <div>
                    <label class="text-sm text-gray-600">RUC</label>
                    <input type="text" name="billing_ruc" id="billingRuc" value="{{ old('billing_ruc', $sale->billing_ruc ?? $sale->client->ruc ?? '') }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600">Razón social (opcional)</label>
                    <input type="text" name="billing_business_name" id="billingBusinessName" value="{{ old('billing_business_name', $sale->billing_business_name ?? $sale->client->business_name ?? '') }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Vence</label>
                    <input type="date" name="due_date" id="dueDate" value="{{ old('due_date', $sale->due_date?->format('Y-m-d')) }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Teléfono</label>
                    <input type="text" name="billing_phone" id="billingPhone" value="{{ old('billing_phone', $sale->billing_phone ?? $sale->client->phone ?? '') }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Email</label>
                    <input type="email" name="billing_email" id="billingEmail" value="{{ old('billing_email', $sale->billing_email ?? $sale->client->email ?? '') }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>

                <div class="md:col-span-3">
                    <label class="text-sm text-gray-600">Dirección</label>
                    <input type="text" name="billing_address" id="billingAddress" value="{{ old('billing_address', $sale->billing_address ?? $sale->client->address ?? '') }}"
                           class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                </div>
            </div>
        </div>

        {{-- Productos --}}
        <div class="bg-white p-4 rounded-xl shadow space-y-3">

            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">Detalle de Productos</h3>

                <button type="button" id="addItemBtn" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-sm">
                    + Agregar Producto
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="itemsTable">

                    <thead class="bg-green-700 text-white">
                        <tr>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-left">Cantidad</th>
                            <th class="px-3 py-2 text-left">Precio</th>
                            <th class="px-3 py-2 text-left">Subtotal</th>
                            <th class="px-3 py-2 text-center">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white" id="itemsBody">
                        <!-- filas dinámicas -->
                    </tbody>

                </table>
            </div>
        </div>

        {{-- Totales --}}
        <div class="flex justify-end">

            <div class="bg-white p-4 rounded-xl shadow w-full md:w-1/3 space-y-2">

                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span id="subtotal">C$ 0.00</span>
                </div>

                <div class="flex justify-between text-sm">
                    <span>IVA (15%)</span>
                    <span id="tax">C$ 0.00</span>
                </div>

                <div class="border-t pt-3 flex justify-between text-lg font-bold text-green-700">
                    <span>Total</span>
                    <span id="total">C$ 0.00</span>
                </div>

            </div>

        </div>

        {{-- Observaciones --}}
        <div class="bg-white p-4 rounded-xl shadow space-y-3">
            <h3 class="text-lg font-semibold text-gray-700">Observaciones</h3>
            <textarea name="notes" rows="3" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white"
                      placeholder="Notas internas / para imprimir">{{ old('notes', $sale->notes) }}</textarea>
        </div>

        {{-- Botones --}}
        <div class="flex justify-end space-x-4 mt-4">

            <a href="{{ route('facturacion.show', $sale->id) }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 inline-block">
                Cancelar
            </a>

            <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow inline-block">
                Actualizar Factura
            </button>

        </div>

    </form>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const products = JSON.parse(document.getElementById('invoiceApp').dataset.products).map(p => ({
            id: p.id,
            name: p.name,
            price: parseFloat(p.sale_price ?? 0)
        }));
        const clients = JSON.parse(document.getElementById('invoiceApp').dataset.clients).map(c => ({
            id: c.id,
            name: c.name ?? '',
            business_name: c.business_name ?? '',
            ruc: c.ruc ?? '',
            phone: c.phone ?? '',
            email: c.email ?? '',
            address: c.address ?? '',
        }));
        const sale = JSON.parse(document.getElementById('invoiceApp').dataset.sale);
        let index = 0;

        function formatMoney(v){ return 'C$ ' + parseFloat(v||0).toFixed(2); }

        function recalc(){
            let subtotal = 0;
            document.querySelectorAll('#itemsBody tr').forEach(tr=>{
                const q = parseFloat(tr.querySelector('[name^="items"][name$="[quantity]"]').value)||0;
                const p = parseFloat(tr.querySelector('[name^="items"][name$="[price]"]').value)||0;
                const s = q*p; subtotal += s;
                tr.querySelector('.row-subtotal').textContent = formatMoney(s);
            });
            const rate = 0.15;
            const included = document.getElementById('taxIncluded')?.value === '1';
            let subtotalExcl = 0;
            let tax = 0;
            let total = 0;

            if (included) {
                subtotalExcl = subtotal / (1 + rate);
                tax = subtotal - subtotalExcl;
                total = subtotal;
            } else {
                subtotalExcl = subtotal;
                tax = subtotal * rate;
                total = subtotal + tax;
            }

            document.getElementById('subtotal').textContent = formatMoney(subtotalExcl);
            document.getElementById('tax').textContent = formatMoney(tax);
            document.getElementById('total').textContent = formatMoney(total);
        }

        function addRow(item){
            item = item||{};
            const i = index++;
            const tr = document.createElement('tr'); tr.className='border-t';
            tr.innerHTML = `
                <td class="px-4 py-2">
                    <select name="items[${i}][product_id]" class="product-select">
                        ${products.map(p=>`<option value="${p.id}" ${item.product_id==p.id?'selected':''} data-price="${p.price}">${p.name}</option>`).join('')}
                    </select>
                </td>
                <td class="px-4 py-2"><input type="number" name="items[${i}][quantity]" value="${item.quantity||1}" min="1" class="w-20"/></td>
                <td class="px-4 py-2"><input type="number" step="0.01" name="items[${i}][price]" value="${item.price ?? (products[0]?.price ?? 0)}" class="w-28"/></td>
                <td class="px-4 py-2 row-subtotal">${formatMoney((item.quantity||1)*(item.price||0))}</td>
                <td class="px-4 py-2 text-center"><button type="button" class="remove-row bg-red-500 text-white px-2 py-1 rounded text-xs">Eliminar</button></td>
            `;
            document.getElementById('itemsBody').appendChild(tr);
            tr.querySelector('.product-select').addEventListener('change', function(){
                const opt = this.selectedOptions[0];
                tr.querySelector('[name$="[price]"]').value = opt.dataset.price;
                recalc();
            });
            tr.querySelectorAll('input').forEach(inp=>inp.addEventListener('input', recalc));
            tr.querySelector('.remove-row').addEventListener('click', function(){
                tr.remove();
                recalc();
            });
        }

        // Load existing items
        sale.details.forEach(detail => addRow({
            product_id: detail.product_id,
            quantity: detail.quantity,
            price: detail.price
        }));

        // Autofill billing fields when changing client
        const clientSelect = document.getElementById('clientSelect');
        const billingName = document.getElementById('billingName');
        const billingBusinessName = document.getElementById('billingBusinessName');
        const billingRuc = document.getElementById('billingRuc');
        const billingPhone = document.getElementById('billingPhone');
        const billingEmail = document.getElementById('billingEmail');
        const billingAddress = document.getElementById('billingAddress');

        function setBillingFromClientId(id) {
            const c = clients.find(x => String(x.id) === String(id));
            if (!c) return;
            billingName.value = c.name || billingName.value;
            billingBusinessName.value = c.business_name || billingBusinessName.value;
            billingRuc.value = c.ruc || billingRuc.value;
            billingPhone.value = c.phone || billingPhone.value;
            billingEmail.value = c.email || billingEmail.value;
            billingAddress.value = c.address || billingAddress.value;
        }

        clientSelect?.addEventListener('change', (e) => setBillingFromClientId(e.target.value));
        document.getElementById('taxIncluded')?.addEventListener('change', recalc);

        document.getElementById('addItemBtn').addEventListener('click', ()=>addRow());
        recalc();
    });
</script>
@endpush

@endsection