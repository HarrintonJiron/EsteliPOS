<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
@page{margin:30px 34px 42px}body{font-family:DejaVu Sans,sans-serif;color:#1e293b;font-size:9px}.header{border-bottom:2px solid #0f766e;padding-bottom:10px;margin-bottom:16px}h1{margin:0;font-size:20px;color:#0f172a}.company{color:#0f766e;font-size:12px;font-weight:bold;margin-bottom:4px}.period{color:#64748b;margin-top:4px}table{width:100%;border-collapse:collapse}th{background:#1e293b;color:white;padding:7px 5px;text-align:left;font-size:8px}td{border-bottom:1px solid #e2e8f0;padding:5px;vertical-align:top}td:nth-last-child(-n+3){white-space:nowrap}tr:nth-child(even){background:#f8fafc}.footer{position:fixed;bottom:-26px;left:0;right:0;color:#64748b;font-size:8px;border-top:1px solid #cbd5e1;padding-top:5px}.page-number:after{content:counter(page)}
</style></head><body>
<div class="header"><div class="company">{{ $company }}</div><h1>{{ $title }}</h1><div class="period">{{ $period }}</div></div>
<table><thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ is_numeric($value) && $value !== '' ? number_format((float)$value, 2) : $value }}</td>@endforeach</tr>
@empty<tr><td colspan="{{ count($headers) }}">No hay movimientos para los filtros seleccionados.</td></tr>@endforelse
</tbody></table><div class="footer">Generado: {{ $generatedAt->format('d/m/Y H:i') }} <span style="float:right">Página <span class="page-number"></span></span></div>
</body></html>
