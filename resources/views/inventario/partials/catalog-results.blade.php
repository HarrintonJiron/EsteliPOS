<div class="inventory-mobile-list">
    @forelse($products as $product)
        <a href="{{ route('inventario.show', $product->id) }}" class="inventory-mobile-card">
            <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-mono text-xs font-bold text-indigo-600">{{ $product->code }}</p>
                    <p class="truncate font-semibold text-slate-900">{{ $product->name }}</p>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $product->category->name ?? 'Sin categoría' }}</p>
                </div>
                <span class="badge-{{ match($product->inventory_status) { 'expired' => 'danger', 'expiring_soon', 'low_stock' => 'warning', default => 'success' } }} shrink-0 text-[10px]">
                    {{ $product->inventory_status_label }}
                </span>
            </div>
            <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-50 p-2.5 text-sm">
                <div><span class="block text-[10px] font-semibold uppercase text-slate-400">Existencia</span><strong class="{{ $product->stock <= 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-amber-600' : 'text-emerald-600') }}">{{ number_format((float) $product->stock, 2) }} {{ $product->baseUnitLabel() }}</strong></div>
                <div class="text-right"><span class="block text-[10px] font-semibold uppercase text-slate-400">Precio</span><strong class="text-slate-900">C$ {{ number_format($product->sale_price, 2) }}</strong></div>
            </div>
        </a>
    @empty
        <div class="p-6 text-center text-sm text-slate-500">
            {{ request('q') ? 'Sin resultados para «'.request('q').'»' : 'Sin productos' }}
        </div>
    @endforelse
</div>

<div class="inventory-desktop-table overflow-x-auto">
    <table class="min-w-full text-xs">
        <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-2 py-2 font-semibold">Producto</th>
                <th class="px-2 py-2 font-semibold">Cat.</th>
                <th class="px-2 py-2 font-semibold text-right">Stock</th>
                @if($viewMode !== 'list')
                <th class="px-2 py-2 font-semibold text-right">Vend.</th>
                <th class="px-2 py-2 font-semibold text-right">Rot.</th>
                @endif
                <th class="px-2 py-2 font-semibold text-right">Precio</th>
                <th class="px-2 py-2 font-semibold">Estado</th>
                <th class="px-2 py-2 font-semibold text-right"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($products as $product)
            <tr class="hover:bg-slate-50/80 {{ $product->isExpired() ? 'bg-red-50/60' : ($product->isLowStock() ? 'bg-amber-50/40' : '') }}">
                <td class="px-2 py-1.5">
                    <p class="font-mono font-semibold text-indigo-600">{{ $product->code }}</p>
                    <p class="max-w-[200px] truncate font-medium text-slate-800">{{ $product->name }}</p>
                </td>
                <td class="px-2 py-1.5 text-slate-600">{{ $product->category->name ?? '—' }}</td>
                <td class="px-2 py-1.5 text-right">
                    <span class="font-bold {{ $product->stock <= 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-amber-600' : 'text-emerald-600') }}">
                        {{ number_format((float) $product->stock, 2) }}
                    </span>
                    <span class="block text-[10px] text-slate-400">{{ $product->baseUnitLabel() }}</span>
                </td>
                @if($viewMode !== 'list')
                <td class="px-2 py-1.5 text-right font-semibold">{{ (int) ($product->sold_qty ?? 0) }}</td>
                <td class="px-2 py-1.5 text-right">
                    @php $rot = (float) ($product->rotation_index ?? 0); @endphp
                    <span class="{{ $rot >= 1 ? 'text-emerald-600' : ($rot > 0 ? 'text-amber-600' : 'text-red-600') }}">{{ number_format($rot, 1) }}x</span>
                </td>
                @endif
                <td class="px-2 py-1.5 text-right font-semibold text-slate-800">C$ {{ number_format($product->sale_price, 2) }}</td>
                <td class="px-2 py-1.5">
                    <span class="badge-{{ match($product->inventory_status) { 'expired' => 'danger', 'expiring_soon', 'low_stock' => 'warning', default => 'success' } }} text-[10px]">
                        {{ $product->inventory_status_label }}
                    </span>
                </td>
                <td class="px-2 py-1.5 text-right whitespace-nowrap">
                    <a href="{{ route('inventario.show', $product->id) }}" class="text-indigo-600 hover:underline">Ver</a>
                    @if(auth()->user()?->isAdmin())
                    · <a href="{{ route('inventario.edit', $product->id) }}" class="text-slate-500 hover:underline">Edit.</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $viewMode !== 'list' ? 8 : 6 }}" class="px-2 py-8 text-center text-slate-500">
                    @if(request('q'))
                        Sin resultados para «{{ request('q') }}»
                    @else
                        Sin productos
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($products->hasPages())
<div class="border-t border-slate-100 px-2 py-2 text-xs">{{ $products->links() }}</div>
@endif
