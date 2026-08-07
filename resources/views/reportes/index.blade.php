@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
@php
    $reportTabs = [
        ['id' => 'sales', 'label' => 'Ventas', 'active' => $reportType === 'sales'],
        ['id' => 'purchases', 'label' => 'Compras', 'active' => $reportType === 'purchases'],
        ['id' => 'inventory', 'label' => 'Inventario', 'active' => $reportType === 'inventory'],
        ['id' => 'kardex', 'label' => 'Kardex', 'active' => $reportType === 'kardex'],
        ['id' => 'profit', 'label' => 'Rentabilidad', 'active' => $reportType === 'profit'],
    ];
    $periodLabel = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' — ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
@endphp

<div class="page-shell">

    <x-ui.page-header
        title="Reportes y análisis"
        :subtitle="'Información operativa para tu PYME · ' . $periodLabel"
    >
        <x-slot:actions>
            <x-ui.export-bar
                csv-route="reportes.export"
                :csv-query="request()->except('page')"
            />
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tabs :items="$reportTabs" param="report_type" />

    <form method="GET" action="{{ route('reportes.index') }}" class="filter-panel no-print" id="report-filters">
        <input type="hidden" name="report_type" value="{{ $reportType }}">

        <div class="filter-grid">
            <div>
                <label class="form-label" for="start_date">Desde</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" class="input-field">
            </div>
            <div>
                <label class="form-label" for="end_date">Hasta</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" class="input-field">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1">Generar reporte</button>
            </div>
        </div>

        <div class="date-presets mt-3 pt-3 border-t border-slate-100">
            <button type="button" class="date-preset" data-preset="today">Hoy</button>
            <button type="button" class="date-preset" data-preset="month">Este mes</button>
            <button type="button" class="date-preset" data-preset="last_month">Mes anterior</button>
            <button type="button" class="date-preset" data-preset="quarter">Trimestre</button>
        </div>

        @if(in_array($reportType, ['sales', 'purchases', 'inventory', 'kardex']))
        <div class="filter-grid mt-4 pt-4 border-t border-slate-100">
            @if($reportType === 'sales')
                <div>
                    <label class="form-label" for="client_id">Cliente</label>
                    <select id="client_id" name="client_id" class="select-field">
                        <option value="">Todos</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="status">Estado</label>
                    <select id="status" name="status" class="select-field">
                        <option value="">Todos</option>
                        <option value="completed" @selected(request('status') === 'completed')>Pagada</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="payment_type">Condición</label>
                    <select id="payment_type" name="payment_type" class="select-field">
                        <option value="">Todas</option>
                        <option value="cash" @selected(request('payment_type') === 'cash')>Contado</option>
                        <option value="credit" @selected(request('payment_type') === 'credit')>Crédito</option>
                    </select>
                </div>
            @elseif($reportType === 'purchases')
                <div>
                    <label class="form-label" for="supplier_id">Proveedor</label>
                    <select id="supplier_id" name="supplier_id" class="select-field">
                        <option value="">Todos</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="status">Estado</label>
                    <select id="status" name="status" class="select-field">
                        <option value="">Todos</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completada</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                    </select>
                </div>
            @elseif(in_array($reportType, ['inventory', 'kardex']))
                <div>
                    <label class="form-label" for="product_id">Producto</label>
                    <select id="product_id" name="product_id" class="select-field">
                        <option value="">Todos</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($reportType === 'kardex')
                    <div>
                        <label class="form-label" for="type">Movimiento</label>
                        <select id="type" name="type" class="select-field">
                            <option value="">Todos</option>
                            <option value="in" @selected(request('type') === 'in')>Entrada</option>
                            <option value="out" @selected(request('type') === 'out')>Salida</option>
                        </select>
                    </div>
                @else
                    <div>
                        <label class="form-label" for="stock_status">Stock</label>
                        <select id="stock_status" name="stock_status" class="select-field">
                            <option value="">Todos</option>
                            <option value="low" @selected(request('stock_status') === 'low')>Stock bajo</option>
                            <option value="out" @selected(request('stock_status') === 'out')>Sin stock</option>
                            <option value="expired" @selected(request('stock_status') === 'expired')>Vencido</option>
                            <option value="expiring_soon" @selected(request('stock_status') === 'expiring_soon')>Por vencer</option>
                        </select>
                    </div>
                @endif
            @endif
        </div>
        @endif
    </form>

    @if(! empty($summary))
        @include('reportes._summary')
    @endif

    @include('reportes._table')

</div>

@push('scripts')
<script>
document.querySelectorAll('.date-preset').forEach(btn => {
    btn.addEventListener('click', () => {
        const form = document.getElementById('report-filters');
        const start = form.querySelector('[name=start_date]');
        const end = form.querySelector('[name=end_date]');
        const today = new Date();
        const fmt = d => d.toISOString().slice(0, 10);

        switch (btn.dataset.preset) {
            case 'today':
                start.value = end.value = fmt(today);
                break;
            case 'month':
                start.value = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
                end.value = fmt(today);
                break;
            case 'last_month':
                start.value = fmt(new Date(today.getFullYear(), today.getMonth() - 1, 1));
                end.value = fmt(new Date(today.getFullYear(), today.getMonth(), 0));
                break;
            case 'quarter':
                start.value = fmt(new Date(today.getFullYear(), today.getMonth() - 2, 1));
                end.value = fmt(today);
                break;
        }
        form.submit();
    });
});
</script>
@endpush
@endsection
