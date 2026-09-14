@php($editing = $branch->exists)
<form method="POST" action="{{ $editing ? route('sucursales.update', $branch) : route('sucursales.store') }}" class="card p-6 space-y-6">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="grid gap-5 md:grid-cols-2">
        <label class="block"><span class="form-label">Código *</span><input name="code" value="{{ old('code', $branch->code) }}" class="input-field" required maxlength="30"></label>
        <label class="block"><span class="form-label">Nombre *</span><input name="name" value="{{ old('name', $branch->name) }}" class="input-field" required></label>
        <label class="block"><span class="form-label">Tipo *</span><select name="type" class="input-field" required>@foreach($types as $value => $label)<option value="{{ $value }}" @selected(old('type', $branch->type) === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="block"><span class="form-label">Ciudad</span><input name="city" value="{{ old('city', $branch->city) }}" class="input-field"></label>
        <label class="block md:col-span-2"><span class="form-label">Dirección</span><input name="address" value="{{ old('address', $branch->address) }}" class="input-field"></label>
        <label class="block"><span class="form-label">Teléfono</span><input name="phone" value="{{ old('phone', $branch->phone) }}" class="input-field"></label>
        <label class="block"><span class="form-label">Encargado</span><input name="manager_name" value="{{ old('manager_name', $branch->manager_name) }}" class="input-field"></label>
        <label class="block"><span class="form-label">Bodega vinculada</span><select name="warehouse_id" class="input-field"><option value="">Sin bodega vinculada</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $branch->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select><small class="text-slate-500">Las ventas y existencias se calculan desde esta bodega.</small></label>
        <label class="block"><span class="form-label">Lista de precios de la sucursal</span><select name="price_list_id" class="input-field"><option value="">Usar lista general</option>@foreach($priceLists as $priceList)<option value="{{ $priceList->id }}" @selected((string) old('price_list_id', $branch->price_list_id) === (string) $priceList->id)>{{ $priceList->code }} · {{ $priceList->name }}</option>@endforeach</select><small class="text-slate-500">Se aplica al vender desde la bodega vinculada, salvo que el cliente tenga una lista especial.</small></label>
        <label class="block"><span class="form-label">Centro de costo</span><select name="cost_center_id" class="input-field"><option value="">Sin centro de costo</option>@foreach($costCenters as $center)<option value="{{ $center->id }}" @selected((string) old('cost_center_id', $branch->cost_center_id) === (string) $center->id)>{{ $center->code }} · {{ $center->name }}</option>@endforeach</select></label>
        <label class="flex items-center gap-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $branch->is_active)) class="rounded border-slate-300"><span class="font-medium text-slate-700">Sucursal activa</span></label>
        <label class="block md:col-span-2"><span class="form-label">Notas</span><textarea name="notes" rows="3" class="input-field">{{ old('notes', $branch->notes) }}</textarea></label>
    </div>

    @if($errors->any())<div class="alert-danger"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="flex justify-end gap-3"><a href="{{ $editing ? route('sucursales.show', $branch) : route('sucursales.index') }}" class="btn-outline">Cancelar</a><button class="btn-primary">{{ $editing ? 'Guardar cambios' : 'Crear sucursal' }}</button></div>
</form>
