@php
    $titles = [
        'sales' => 'Detalle de ventas',
        'purchases' => 'Detalle de compras',
        'inventory' => 'Estado del inventario',
        'kardex' => 'Kardex de movimientos',
        'profit' => 'Análisis de rentabilidad',
        'abc' => 'Clasificación ABC de productos',
        'aging' => 'Antigüedad de cartera',
        'slow' => 'Productos de lenta rotación',
        'top_clients' => 'Clientes con mayor compra',
        'sellers' => 'Desempeño de vendedores',
        'categories' => 'Ventas por categoría',
    ];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titles[$reportType] ?? 'Reporte' }}</title>
    <style>
        @page { margin: 15mm 12mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 9px; }
        header { border-bottom: 2px solid #172033; padding-bottom: 10px; margin-bottom: 13px; }
        h1 { margin: 0 0 4px; font-size: 17px; }
        h2 { margin: 0 0 6px; font-size: 12px; }
        header p { margin: 2px 0; color: #475569; }
        .data-card-header { margin-bottom: 6px; }
        .data-card-title { display: none; }
        .data-card-meta { color: #475569; }
        .data-card-body { width: 100%; }
        table { border-collapse: collapse; width: 100%; table-layout: auto; }
        thead { display: table-header-group; }
        th { background: #e9eef4; color: #172033; text-align: left; font-weight: bold; }
        th, td { border-bottom: 1px solid #d8e0e8; padding: 5px 6px; vertical-align: top; overflow-wrap: anywhere; }
        tr { page-break-inside: avoid; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-semibold, .font-bold { font-weight: bold; }
        a { color: inherit; text-decoration: none; }
        .no-print, .data-card-footer, button, nav { display: none !important; }
        footer { position: fixed; bottom: -9mm; left: 0; right: 0; border-top: 1px solid #d8e0e8; padding-top: 4px; color: #64748b; font-size: 8px; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $companyProfile['company_name'] ?? config('app.name', 'EsteliPOS') }}</h1>
        <h2>{{ $titles[$reportType] ?? 'Reporte' }}</h2>
        <p>Período: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
        <p>Generado: {{ now()->format('d/m/Y H:i') }} · {{ number_format($data->total()) }} registros</p>
    </header>

    @include('reportes._table')

    <footer>EsteliPOS · Reporte generado por el sistema</footer>
</body>
</html>
