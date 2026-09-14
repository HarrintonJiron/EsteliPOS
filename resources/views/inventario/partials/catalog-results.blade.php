<div class="overflow-x-auto">
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
