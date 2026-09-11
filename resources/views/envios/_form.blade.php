@php($shipment = $shipment ?? null)
@php($editing = (bool) $shipment?->exists)

<section class="card p-5 sm:p-6">
    <div class="mb-5 flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-xl">🚚</span>
        <div><h2 class="font-bold text-slate-900">Datos para el ticket</h2><p class="text-sm text-slate-500">Solo nombre, contacto y lugar de entrega.</p></div>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <label class="text-sm font-semibold text-slate-700">Nombre de quien recibe <span class="text-rose-500">*</span><input id="recipient-name" name="recipient_name" class="input-field mt-2" required autofocus value="{{ old('recipient_name',$shipment?->recipient_name) }}" placeholder="Ej. María López"></label>
        <label class="text-sm font-semibold text-slate-700">Teléfono <span class="font-normal text-slate-400">(opcional)</span><input id="recipient-phone" type="tel" name="recipient_phone" class="input-field mt-2" value="{{ old('recipient_phone',$shipment?->recipient_phone) }}" placeholder="8888 8888" inputmode="tel"></label>
        <label class="text-sm font-semibold text-slate-700">Departamento <span class="text-rose-500">*</span><select name="department" class="select-field mt-2" required><option value="">Seleccionar…</option>@foreach($departments as $department)<option @selected(old('department',$shipment?->department)===$department)>{{ $department }}</option>@endforeach</select></label>
        <label class="text-sm font-semibold text-slate-700">Municipio o ciudad <span class="font-normal text-slate-400">(opcional)</span><input name="municipality" class="input-field mt-2" value="{{ old('municipality',$shipment?->municipality) }}" placeholder="Ej. Sébaco"></label>
        <div class="md:col-span-2"><label class="text-sm font-semibold text-slate-700">Manejo del paquete <span class="text-rose-500">*</span><input id="package-handling" name="address" class="input-field mt-2" required value="{{ old('address',$shipment?->address) }}" placeholder="Ej. Frágil · No voltear"></label><div class="mt-2 flex flex-wrap gap-2">@foreach(['Frágil','No voltear','Mantener seco','Entregar personalmente'] as $handling)<button type="button" class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-100" onclick="document.getElementById('package-handling').value=this.textContent.trim()">{{ $handling }}</button>@endforeach</div></div>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Referencia breve <span class="font-normal text-slate-400">(opcional)</span><input name="reference" class="input-field mt-2" value="{{ old('reference',$shipment?->reference) }}" placeholder="Frente al parque, casa azul…"></label>
    </div>
</section>

<details class="card overflow-hidden" @if($editing || $errors->hasAny(['sale_id','client_id','carrier','tracking_number','shipping_cost','status','notes'])) open @endif>
    <summary class="cursor-pointer list-none p-5 font-semibold text-slate-700 hover:bg-slate-50"><span class="mr-2">⚙️</span> Opciones adicionales <span class="font-normal text-slate-400">(no necesarias para imprimir)</span></summary>
    <div class="grid gap-4 border-t border-slate-100 p-5 md:grid-cols-2">
        <label class="text-sm font-semibold text-slate-700">Cliente registrado<select id="shipment-client" name="client_id" class="select-field mt-2"><option value="">Sin relacionar</option>@foreach($clients as $client)<option value="{{ $client->id }}" data-name="{{ $client->name }}" data-phone="{{ $client->phone }}" @selected(old('client_id',$shipment?->client_id)==$client->id)>{{ $client->name }}{{ $client->phone ? ' · '.$client->phone : '' }}</option>@endforeach</select></label>
        <label class="text-sm font-semibold text-slate-700">Venta relacionada<select name="sale_id" class="select-field mt-2"><option value="">Sin factura</option>@foreach($sales as $sale)<option value="{{ $sale->id }}" @selected(old('sale_id',$shipment?->sale_id)==$sale->id)>{{ $sale->invoice_number }} · C$ {{ number_format($sale->total,2) }}</option>@endforeach</select></label>
        <label class="text-sm font-semibold text-slate-700">Transportista<input name="carrier" list="carrier-options" class="input-field mt-2" value="{{ old('carrier',$shipment?->carrier) }}" placeholder="Ej. Cargotrans"><datalist id="carrier-options"><option value="Cargotrans"><option value="Transagro"><option value="Bus interlocal"><option value="Mensajería propia"></datalist></label>
        <label class="text-sm font-semibold text-slate-700">Número de guía<input name="tracking_number" class="input-field mt-2" value="{{ old('tracking_number',$shipment?->tracking_number) }}" placeholder="Código de rastreo"></label>
        <label class="text-sm font-semibold text-slate-700">Costo de envío<input type="number" step="0.01" min="0" name="shipping_cost" class="input-field mt-2" value="{{ old('shipping_cost',$shipment?->shipping_cost ?? 0) }}"></label>
        @if($editing)<label class="text-sm font-semibold text-slate-700">Estado<select name="status" class="select-field mt-2">@foreach(['pending'=>'Pendiente','prepared'=>'Preparado','shipped'=>'Enviado','delivered'=>'Entregado','cancelled'=>'Cancelado'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$shipment?->status)===$value)>{{ $label }}</option>@endforeach</select></label>@endif
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Notas internas<textarea name="notes" rows="2" class="input-field mt-2" placeholder="Indicaciones para el equipo…">{{ old('notes',$shipment?->notes) }}</textarea></label>
    </div>
</details>

<script>document.addEventListener('DOMContentLoaded',()=>{const client=document.getElementById('shipment-client');client?.addEventListener('change',()=>{const option=client.selectedOptions[0];if(!option?.value)return;const name=document.getElementById('recipient-name'),phone=document.getElementById('recipient-phone');if(!name.value)name.value=option.dataset.name||'';if(!phone.value)phone.value=option.dataset.phone||'';});});</script>
