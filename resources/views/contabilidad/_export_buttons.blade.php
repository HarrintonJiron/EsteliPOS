<div class="flex flex-wrap gap-2 no-print">
    <a href="{{ route('contabilidad.reportes.pdf', ['report' => $report] + request()->query()) }}" class="btn-outline" target="_blank">PDF</a>
    <a href="{{ route('contabilidad.reportes.excel', ['report' => $report] + request()->query()) }}" class="btn-outline">Excel</a>
    <button type="button" class="btn-outline" onclick="window.print()">Imprimir</button>
</div>
