@extends('layouts.app')

@section('title', 'Nueva Factura')

@section('content')

<div id="invoiceApp" class="space-y-4" data-products='@json($products)' data-clients='@json($clients)'>

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <div class="space-y-4">

                {{-- Mensajes --}}
                @if(session('success'))
                    <div class="bg-green-100 text-green-800 px-4 py-3 rounded">
                        {{ session('success') }}
                        @if(session('sale_id'))
                            <a href="{{ route('facturacion.print', ['sale_id' => session('sale_id')]) }}" target="_blank" class="ml-4 text-sm text-green-700 underline">Imprimir</a>
                        @endif
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 text-red-800 px-4 py-3 rounded">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Encabezado --}}
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-700">Nueva Factura</h2>
                        <p class="text-sm text-gray-500">Registro de venta de productos agrícolas</p>
                    </div>

                    <span class="bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm">
                        Factura nueva
                    </span>
                </div>

                <form action="{{ route('facturacion.store') }}" method="POST" id="invoiceForm">
                    @csrf

                    {{-- Datos principales --}}
                    <div class="bg-white p-4 rounded-xl shadow space-y-4">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                            <div>
                                <label class="text-sm text-gray-600">Cliente</label>
                                <select name="client_id" required id="clientSelect" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                                    <option value="">-- seleccionar --</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->code ? ($c->code . ' - ') : '' }}{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">No. Factura</label>
                                <input type="text" name="invoice_number" id="invoiceNumber"
                                       value="{{ old('invoice_number', $nextInvoiceNumber ?? '') }}"
                                       class="mt-1 w-full border rounded-lg px-4 py-2 bg-gray-50" readonly>
                                <p class="text-xs text-gray-400 mt-1">Consecutivo automático</p>
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">Fecha</label>
                                <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                                    class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">Método de Pago</label>
                                <select name="payment_type" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                                    <option value="cash">Efectivo</option>
                                    <option value="transfer">Transferencia</option>
                                    <option value="credit">Crédito</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">IVA</label>
                                <select name="tax_included" id="taxIncluded" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                                    <option value="0">Sin IVA (se suma 15%)</option>
                                    <option value="1">Con IVA (incluido en precios)</option>
                                </select>
                            </div>

                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-sm text-gray-600">Nombre a facturar</label>
                                <input type="text" name="billing_name" id="billingName" required value="{{ old('billing_name') }}"
                                       placeholder="Nombre del cliente / contacto"
                                       class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">RUC</label>
                                <input type="text" name="billing_ruc" id="billingRuc" value="{{ old('billing_ruc') }}"
                                       placeholder="001-123456-0000A"
                                       class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-sm text-gray-600">Razón social (opcional)</label>
                                <input type="text" name="billing_business_name" id="billingBusinessName" value="{{ old('billing_business_name') }}"
                                       placeholder="Empresa / Cooperativa"
                                       class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">Vence</label>
                                <input type="date" name="due_date" id="dueDate" value="{{ old('due_date') }}"
                                       class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                                <p class="text-xs text-gray-400 mt-1">Sugerido si es crédito</p>
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">Teléfono</label>
                                <input type="text" name="billing_phone" id="billingPhone" value="{{ old('billing_phone') }}"
                                       placeholder="Ej: 8888-9999"
                                       class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                            </div>

                            <div>
                                <label class="text-sm text-gray-600">Email</label>
                                <input type="email" name="billing_email" id="billingEmail" value="{{ old('billing_email') }}"
                                       placeholder="correo@cliente.com"
                                       class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                            </div>

                            <div class="md:col-span-3">
                                <label class="text-sm text-gray-600">Dirección</label>
                                <input type="text" name="billing_address" id="billingAddress" value="{{ old('billing_address') }}"
                                       placeholder="Municipio, departamento, referencias..."
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
                                  placeholder="Ej: Gracias por su compra. Productos agrícolas garantizados.">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end space-x-4 mt-4">

                        <button type="reset" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                            Cancelar
                        </button>

                        <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow inline-block">
                            Guardar Factura
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
                    code: c.code ?? '',
                    name: c.name ?? '',
                    business_name: c.business_name ?? '',
                    ruc: c.ruc ?? '',
                    phone: c.phone ?? '',
                    email: c.email ?? '',
                    address: c.address ?? '',
                }));
                let index = 0;

                function formatMoney(v){ return 'C$ ' + parseFloat(v||0).toFixed(2); }

                function recalc(){
                    let linesTotal = 0;
                    document.querySelectorAll('#itemsBody tr').forEach(tr=>{
                        const q = parseFloat(tr.querySelector('[name^="items"][name$="[quantity]"]').value)||0;
                        const p = parseFloat(tr.querySelector('[name^="items"][name$="[price]"]').value)||0;
                        const s = q*p; linesTotal += s;
                        tr.querySelector('.row-subtotal').textContent = formatMoney(s);
                    });
                    const rate = 0.15;
                    const included = document.getElementById('taxIncluded')?.value === '1';
                    let subtotalExcl = 0;
                    let tax = 0;
                    let total = 0;

                    if (included) {
                        subtotalExcl = linesTotal / (1 + rate);
                        tax = linesTotal - subtotalExcl;
                        total = linesTotal;
                    } else {
                        subtotalExcl = linesTotal;
                        tax = linesTotal * rate;
                        total = linesTotal + tax;
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
                        <td class="px-4 py-2 font-semibold row-subtotal">C$ 0.00</td>
                        <td class="px-4 py-2 text-center text-red-600 cursor-pointer"><button type="button" class="remove">Eliminar</button></td>
                    `;
                    document.getElementById('itemsBody').appendChild(tr);

                    // wire events
                    const select = tr.querySelector('select');
                    const qty = tr.querySelector('input[type="number"][name$="[quantity]"]');
                    const price = tr.querySelector('input[type="number"][name$="[price]"]');

                    // set initial price from selected option
                    const setPriceFromSelect = () => {
                        const opt = select.options[select.selectedIndex];
                        const p = parseFloat(opt.dataset.price) || 0;
                        price.value = p.toFixed(2);
                        recalc();
                    };

                    select.addEventListener('change', setPriceFromSelect);
                    qty.addEventListener('input', recalc);
                    price.addEventListener('input', recalc);
                    tr.querySelector('.remove').addEventListener('click', ()=>{ tr.remove(); recalc(); });

                    // initialize
                    setPriceFromSelect();
                }

                document.getElementById('addItemBtn').addEventListener('click', ()=> addRow());
                // add initial row
                addRow();

                // Autofill billing fields from selected client
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

                // reset items after successful save using pure JS
                const successIndicator = document.getElementById('success-indicator');
                if (successIndicator && successIndicator.textContent.trim() === 'success') {
                    document.getElementById('itemsBody').innerHTML = '';
                    index = 0;
                    addRow();
                    recalc();
                }
                });
            </script>

            @if(session('success'))
                <div id="success-indicator" style="display: none;">success</div>
            @endif

            @endpush

@endsection
