@php
    $tableTitles = [
        'sales' => 'Detalle de ventas',
        'purchases' => 'Detalle de compras',
        'inventory' => 'Estado del inventario',
        'kardex' => 'Kardex de movimientos',
        'profit' => 'Análisis de rentabilidad',
    ];
@endphp

<div class="data-card">
    <div class="data-card-header">
        <h3 class="data-card-title">{{ $tableTitles[$reportType] ?? 'Resultados' }}</h3>
        <span class="data-card-meta">{{ $data->total() }} registros</span>
    </div>

    <div class="data-card-body">
        <table class="table-agro min-w-full">
            @if($reportType === 'sales')
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Condición</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">IVA</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('facturacion.show', $sale->id) }}" class="text-link font-medium">
                                    {{ $sale->invoice_number ?? '#' . str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>{{ $sale->date->format('d/m/Y') }}</td>
                            <td>{{ $sale->billing_name ?? $sale->client?->name ?? 'N/A' }}</td>
                            <td>{{ $sale->payment_type === 'credit' ? 'Crédito' : 'Contado' }}</td>
                            <td class="text-right">C$ {{ number_format($sale->subtotal, 2) }}</td>
                            <td class="text-right">C$ {{ number_format($sale->tax_total, 2) }}</td>
                            <td class="text-right font-semibold">C$ {{ number_format($sale->total, 2) }}</td>
                            <td class="text-center">
                                <span class="{{ $sale->status === 'completed' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $sale->status === 'completed' ? 'Pagada' : 'Pendiente' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">@include('reportes._empty')</td></tr>
                    @endforelse
                </tbody>

            @elseif($reportType === 'purchases')
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $purchase)
                        <tr>
                            <td class="font-medium">#{{ str_pad($purchase->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $purchase->date->format('d/m/Y') }}</td>
                            <td>{{ $purchase->supplier?->name ?? 'N/A' }}</td>
                            <td class="text-right font-semibold">C$ {{ number_format($purchase->total, 2) }}</td>
                            <td class="text-center">
                                <span class="{{ $purchase->status === 'completed' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $purchase->status === 'completed' ? 'Completada' : 'Pendiente' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">@include('reportes._empty')</td></tr>
                    @endforelse
                </tbody>

            @elseif($reportType === 'inventory')
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-right">Stock</th>
                        <th class="text-right">Precio</th>
                        <th class="text-right">Valor total</th>
                        <th>Vencimiento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $product)
                        <tr class="{{ $product->isExpired() ? 'bg-red-50/60' : ($product->isLowStock() ? 'bg-amber-50/60' : '') }}">
                            <td>{{ $product->code ?? '—' }}</td>
                            <td class="font-medium">{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-right {{ $product->isLowStock() ? 'text-red-600 font-semibold' : '' }}">
                                {{ $product->stock }} {{ $product->unit }}
                            </td>
                            <td class="text-right">C$ {{ number_format($product->sale_price, 2) }}</td>
                            <td class="text-right">C$ {{ number_format($product->stock * $product->purchase_price, 2) }}</td>
                            <td>
                                @if($product->expiry_date)
                                    <span class="{{ $product->isExpired() ? 'text-red-600 font-semibold' : ($product->expiresSoon(30) ? 'text-amber-600' : '') }}">
                                        {{ $product->expiry_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('reportes._empty')</td></tr>
                    @endforelse
                </tbody>

            @elseif($reportType === 'kardex')
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Stock antes</th>
                        <th class="text-right">Stock después</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->product?->name ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="{{ $movement->type === 'in' ? 'badge-success' : 'badge-danger' }}">
                                    {{ $movement->type === 'in' ? 'Entrada' : 'Salida' }}
                                </span>
                            </td>
                            <td class="text-right font-semibold {{ $movement->type === 'in' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $movement->type === 'in' ? '+' : '−' }}{{ $movement->quantity }}
                            </td>
                            <td class="text-right">{{ $movement->stock_before ?? '—' }}</td>
                            <td class="text-right font-medium">{{ $movement->stock_after ?? '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $movement->reference ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('reportes._empty')</td></tr>
                    @endforelse
                </tbody>

            @elseif($reportType === 'profit')
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th class="text-right">Venta</th>
                        <th class="text-right">Costo</th>
                        <th class="text-right">Ganancia</th>
                        <th class="text-right">Margen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $sale)
                        @php
                            $cost = $sale->details->sum(fn ($d) => $d->quantity * ($d->product?->purchase_price ?? 0));
                            $profit = $sale->total - $cost;
                            $margin = $sale->total > 0 ? ($profit / $sale->total) * 100 : 0;
                        @endphp
                        <tr>
                            <td class="font-medium">{{ $sale->invoice_number ?? '#' . $sale->id }}</td>
                            <td>{{ $sale->date->format('d/m/Y') }}</td>
                            <td class="text-right">C$ {{ number_format($sale->total, 2) }}</td>
                            <td class="text-right">C$ {{ number_format($cost, 2) }}</td>
                            <td class="text-right font-semibold {{ $profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                C$ {{ number_format($profit, 2) }}
                            </td>
                            <td class="text-right {{ $margin >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ number_format($margin, 1) }}%
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">@include('reportes._empty')</td></tr>
                    @endforelse
                </tbody>
            @endif
        </table>
    </div>

    @if($data->hasPages())
        <div class="data-card-footer">
            {{ $data->appends(request()->except('page'))->links() }}
        </div>
    @endif
</div>
