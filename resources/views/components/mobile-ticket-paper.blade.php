@props([
    'selector' => '.receipt',
    'desktopContentWidth' => '72mm',
    'mobileContentWidth' => '46mm',
    'mobileColumns' => 'minmax(0, 1fr) 7mm 15mm',
])

@php
    $paperOverride = (string) request()->query('paper', '');
    $mobileUserAgent = preg_match('/Android|iPhone|iPad|iPod|Mobile|Windows Phone/i', (string) request()->userAgent()) === 1;
    $usesMobilePaper = $paperOverride === '50' || ($paperOverride !== '80' && $mobileUserAgent);
    $paperWidth = $usesMobilePaper ? '50mm' : '80mm';
    $contentWidth = $usesMobilePaper ? $mobileContentWidth : $desktopContentWidth;
@endphp

<meta name="ticket-paper-width" content="{{ $usesMobilePaper ? '50' : '80' }}mm">
<style>
    @page { size: {{ $paperWidth }} auto; margin: 0; }

    @if($usesMobilePaper)
    html, body {
        width: {{ $paperWidth }} !important;
        max-width: {{ $paperWidth }} !important;
        min-width: 0 !important;
    }
    {{ $selector }} {
        width: {{ $contentWidth }} !important;
        max-width: {{ $contentWidth }} !important;
        margin-right: auto !important;
        margin-left: auto !important;
        padding: 2mm !important;
        box-shadow: none !important;
    }
    .ticket-logo, .logo { max-width: 42mm !important; max-height: 30mm !important; }
    .info-row { grid-template-columns: 15mm minmax(0, 1fr) !important; gap: 1mm !important; }
    .items-header, .item-main, .items-footer, .header, .item {
        grid-template-columns: {{ $mobileColumns }} !important;
        gap: 0.8mm !important;
    }
    .company-name, .company { font-size: 11pt !important; }
    .company-line, .item-meta, .meta { font-size: 7.5pt !important; }
    .item-amount, .amount { font-size: 8pt !important; }
    @endif

    @media print {
        html, body {
            width: {{ $paperWidth }} !important;
            max-width: {{ $paperWidth }} !important;
            min-width: {{ $paperWidth }} !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }
        {{ $selector }} {
            width: {{ $contentWidth }} !important;
            max-width: {{ $contentWidth }} !important;
            margin: 0 auto !important;
            padding: {{ $usesMobilePaper ? '1.5mm 2mm 3mm' : '2mm 4mm 4mm' }} !important;
            box-shadow: none !important;
        }
        .screen-only, .no-print { display: none !important; }
    }
</style>
