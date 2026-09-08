@php
    $canChangeStatus = auth()->user()?->isAdmin() || auth()->user()?->hasPermission('compras.edit');
    $compact = $compact ?? false;
@endphp

@if($canChangeStatus && $purchase->status === 'ordered')
    @if($compact)
        <a href="{{ route('compras.show', $purchase->id) }}" class="text-blue-600 hover:underline">Recibir</a>
    @else
        <form action="{{ route('compras.status', $purchase->id) }}" method="POST" class="flex flex-wrap items-center gap-2" onsubmit="return confirm('¿Confirmar que la mercadería llegó? Las existencias ingresarán a la bodega seleccionada y se registrará la compra.')">
            @csrf
            <input type="hidden" name="status" value="received">
            <select name="payment_type" class="select-field py-2 text-sm" aria-label="Forma de pago al recibir">
                <option value="credit" @selected($purchase->payment_type === 'credit')>A crédito</option>
                <option value="cash" @selected($purchase->payment_type === 'cash')>Efectivo</option>
                <option value="transfer" @selected($purchase->payment_type === 'transfer')>Transferencia</option>
            </select>
            <button type="submit" class="btn-primary">Confirmar ingreso</button>
        </form>
    @endif
    <form action="{{ route('compras.status', $purchase->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Anular este pedido en proceso? No se moverá inventario.')">
        @csrf
        <input type="hidden" name="status" value="canceled">
        <button type="submit" class="{{ $compact ? 'text-red-600 hover:underline' : 'btn-outline text-red-700' }}">Anular</button>
    </form>
@elseif($canChangeStatus && $purchase->status === 'pending')
    <form action="{{ route('compras.status', $purchase->id) }}" method="POST" class="{{ $compact ? 'inline' : 'flex items-center gap-2' }}" onsubmit="return confirm('¿Registrar el pago de esta factura? La mercadería ya está en inventario; se liquida la deuda con el proveedor.')">
        @csrf
        <input type="hidden" name="status" value="completed">
        @if($compact)
            <input type="hidden" name="payment_type" value="cash">
        @else
            <select name="payment_type" class="select-field py-2 text-sm" aria-label="Forma de pago">
                <option value="cash">Efectivo</option>
                <option value="transfer">Transferencia</option>
            </select>
        @endif
        <button type="submit" class="{{ $compact ? 'text-emerald-600 hover:underline' : 'btn-primary' }}">Pagar</button>
    </form>
    <form action="{{ route('compras.status', $purchase->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Anular esta compra a crédito? Se sacará la mercadería del inventario.')">
        @csrf
        <input type="hidden" name="status" value="canceled">
        <button type="submit" class="{{ $compact ? 'text-red-600 hover:underline' : 'btn-outline text-red-700' }}">Anular</button>
    </form>
@elseif($canChangeStatus && $purchase->status === 'completed')
    <form action="{{ route('compras.status', $purchase->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Anular esta compra pagada? Se revertirá el inventario y el asiento.')">
        @csrf
        <input type="hidden" name="status" value="canceled">
        <button type="submit" class="{{ $compact ? 'text-red-600 hover:underline' : 'btn-outline text-red-700' }}">Anular</button>
    </form>
@endif
