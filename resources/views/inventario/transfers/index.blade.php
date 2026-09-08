@extends('layouts.app')
@section('title', 'Transferencias internas')
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="page-title">Transferencias internas</h1>
            <p class="page-subtitle">Mueve existencias entre bodegas sin comprar ni vender</p>
        </div>
    </div>

    @if(session('success'))
        <div class="card p-3 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="card p-3 bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid xl:grid-cols-3 gap-4">
        <div class="card p-5 space-y-4 xl:col-span-1">
            <div>
                <h2 class="font-semibold text-slate-800">Nueva transferencia</h2>
                <p class="text-xs text-slate-500">Sale de una bodega y entra a otra. El stock total del producto no cambia.</p>
            </div>

            <form method="POST" action="{{ route('inventario.transfers.store') }}" class="space-y-3" id="transferForm">
                @csrf
                <div>
                    <label class="form-label">Producto *</label>
                    <select name="product_id" id="transferProduct" class="select-field" required>
                        <option value="">Seleccionar…</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                {{ $product->name }} ({{ $product->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Bodega origen *</label>
                    <select name="from_warehouse_id" id="transferFrom" class="select-field" required>
                        <option value="">Seleccionar…</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('from_warehouse_id') == $warehouse->id)>
                                {{ $warehouse->name }}{{ $warehouse->is_default ? ' · Principal' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p id="availableStockHint" class="text-xs text-slate-500 mt-1">Disponible en origen: —</p>
                    @error('from_warehouse_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Bodega destino *</label>
                    <select name="to_warehouse_id" class="select-field" required>
                        <option value="">Seleccionar…</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('to_warehouse_id') == $warehouse->id)>
                                {{ $warehouse->name }}{{ $warehouse->is_default ? ' · Principal' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('to_warehouse_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Cantidad *</label>
                    <input type="number" name="quantity" value="{{ old('quantity') }}" step="0.0001" min="0.0001" class="input-field" required>
                    @error('quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Nota</label>
                    <input type="text" name="note" value="{{ old('note') }}" class="input-field" maxlength="500" placeholder="Ej: Reposición de mostrador">
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Registrar transferencia</button>
            </form>
        </div>

        <div class="xl:col-span-2 space-y-4">
            <form method="GET" class="card p-3 flex flex-wrap gap-2 items-center">
                <input type="search" name="q" value="{{ $search }}" class="input-field min-w-0 flex-1 sm:min-w-[220px]" placeholder="Buscar producto, nota o referencia…">
                <button class="btn-primary text-sm">Buscar</button>
                @if($search !== '')
                    <a href="{{ route('inventario.transfers.index') }}" class="btn-outline text-sm">Limpiar</a>
                @endif
            </form>

            <div class="card overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">Historial de transferencias</h2>
                    <p class="text-xs text-slate-500">Movimientos internos entre bodegas</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full table-agro text-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Desde</th>
                                <th>Hacia</th>
                                <th class="text-right">Cantidad</th>
                                <th>Usuario</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                                <tr>
                                    <td class="whitespace-nowrap text-slate-500">{{ optional($transfer['created_at'])->format('d/m/Y H:i') }}</td>
                                    <td class="font-medium">
                                        {{ $transfer['product']?->name ?? '—' }}
                                        <div class="text-xs text-slate-400 font-mono">{{ $transfer['product']?->code }}</div>
                                    </td>
                                    <td>{{ $transfer['from']?->name ?? '—' }}</td>
                                    <td>{{ $transfer['to']?->name ?? '—' }}</td>
                                    <td class="text-right font-semibold">{{ rtrim(rtrim(number_format($transfer['quantity'], 4, '.', ''), '0'), '.') }}</td>
                                    <td>{{ $transfer['user']?->name ?? '—' }}</td>
                                    <td class="text-slate-500 max-w-[220px] truncate" title="{{ $transfer['note'] }}">{{ $transfer['note'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-slate-400">Aún no hay transferencias internas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transfers->hasPages())
                    <div class="p-3 border-t">{{ $transfers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const productSelect = document.getElementById('transferProduct');
    const fromSelect = document.getElementById('transferFrom');
    const hint = document.getElementById('availableStockHint');
    const stockUrl = @json(route('inventario.transfers.stock'));

    async function refreshAvailable() {
        if (!productSelect.value || !fromSelect.value) {
            hint.textContent = 'Disponible en origen: —';
            return;
        }

        const url = new URL(stockUrl, window.location.origin);
        url.searchParams.set('product_id', productSelect.value);
        url.searchParams.set('warehouse_id', fromSelect.value);

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('fail');
            const data = await response.json();
            hint.textContent = `Disponible en origen: ${Number(data.quantity).toLocaleString('es-NI', { maximumFractionDigits: 4 })}`;
        } catch {
            hint.textContent = 'Disponible en origen: no disponible';
        }
    }

    productSelect?.addEventListener('change', refreshAvailable);
    fromSelect?.addEventListener('change', refreshAvailable);
    refreshAvailable();
})();
</script>
@endpush
@endsection
