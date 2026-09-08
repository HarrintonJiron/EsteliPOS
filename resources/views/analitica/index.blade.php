@extends('layouts.app')

@section('title', 'Analítica gerencial')

@section('content')
@php
    $companyName = $companyProfile['company_name'] ?? 'EsteliPOS';
    $change = (float) ($kpis['sales_change'] ?? 0);
    $cashShare = (float) ($kpis['cash_share'] ?? 0);
    $creditShare = max(0, 100 - $cashShare);
    $rankedBranches = collect($branches)->sortByDesc('sales_month')->values();
    $maxBranchSales = max(1, (float) $rankedBranches->max('sales_month'));
    $maxProduct = max(1, (float) collect($top_products)->max('total'));
    $maxClient = max(1, (float) collect($top_clients)->max('total'));
    $maxCenter = max(1, (float) collect($cost_centers)->max('debit'));
    $dailyRows = collect($daily ?? [])->values();
    $sparkMax = max(1.0, (float) $dailyRows->max('sales'));
    $sparkCount = max(1, $dailyRows->count());
    $sparkPoints = $dailyRows->map(function ($row, $index) use ($sparkMax, $sparkCount) {
        $x = $sparkCount > 1 ? ($index / ($sparkCount - 1)) * 100 : 0;
        $y = 30 - ((((float) ($row['sales'] ?? 0)) / $sparkMax) * 26);

        return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
    });
    $sparkLine = $sparkPoints->implode(' ');
    $sparkArea = '0,32 '.$sparkLine.' 100,32';
    $shortcuts = [
        ['href' => route('reportes.index', ['report_type' => 'abc']), 'label' => 'ABC de productos', 'text' => 'Clasificación de rotación'],
        ['href' => route('reportes.index', ['report_type' => 'aging']), 'label' => 'Antigüedad de cartera', 'text' => 'Créditos por tramo'],
        ['href' => route('sucursales.index'), 'label' => 'Red de sucursales', 'text' => 'Scorecard por punto'],
        ['href' => route('rrhh.hub'), 'label' => 'Recursos humanos', 'text' => 'Equipo, INSS y 13°'],
        ['href' => route('contabilidad.centros-costo.analytics'), 'label' => 'Centros de costo', 'text' => 'Gasto por unidad'],
        ['href' => route('reportes.index', ['report_type' => 'top_clients']), 'label' => 'Top clientes', 'text' => 'Concentración de ventas'],
    ];
@endphp

<div class="ex-shell">
    <x-ui.command-hero
        kicker="Sala de control gerencial"
        :title="$companyName"
        :subtitle="'Red Estelí · ' . $period_label . ' · actualizado ' . $generated_at"
        metric-label="Ventas del mes"
        :metric-value="'C$ ' . number_format($kpis['sales_month'], 0)"
        :delta="$change"
        :meta="[number_format($kpis['tickets']) . ' tickets', number_format($cashShare, 0) . '% contado']"
    >
        <x-slot:aside>
            <p class="ex-hero__label">Últimos 14 días</p>
            <svg viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <linearGradient id="exSparkFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#34d399" stop-opacity="0.45"/>
                        <stop offset="100%" stop-color="#34d399" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <polygon points="{{ $sparkArea }}" fill="url(#exSparkFill)"></polygon>
                <polyline points="{{ $sparkLine }}" fill="none" stroke="#34d399" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"></polyline>
            </svg>
            <div class="ex-hero__stats">
                <div>
                    <p class="ex-hero__label">Hoy</p>
                    <p>C$ {{ number_format($kpis['sales_today'], 0) }}</p>
                </div>
                <div>
                    <p class="ex-hero__label">Ticket promedio</p>
                    <p>C$ {{ number_format($kpis['ticket_avg'], 0) }}</p>
                </div>
                <div>
                    <p class="ex-hero__label">Sucursales</p>
                    <p>{{ $rankedBranches->count() }} puntos</p>
                </div>
            </div>
        </x-slot:aside>
        <x-slot:actions>
            <a href="{{ route('sucursales.index') }}" class="ex-btn">Sucursales</a>
            <a href="{{ route('reportes.index') }}" class="ex-btn">Reportes</a>
            <a href="{{ route('contabilidad.dashboard') }}" class="ex-btn ex-btn--solid">Contabilidad</a>
        </x-slot:actions>
    </x-ui.command-hero>

    @if(count($alerts))
        <div class="ex-alerts">
            @foreach($alerts as $alert)
                <a href="{{ $alert['link'] }}" class="ex-alert {{ $alert['type'] === 'danger' ? 'is-danger' : 'is-warn' }}">
                    <span>{{ $alert['type'] === 'danger' ? 'Cartera' : 'Inventario' }}</span>
                    {{ $alert['message'] }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="ex-kpis">
        <article class="ex-kpi">
            <p class="ex-kpi__label">Margen bruto estimado</p>
            <p class="ex-kpi__value">C$ {{ number_format($kpis['margin'], 0) }}</p>
            <p class="ex-kpi__meta">{{ number_format($kpis['margin_pct'], 1) }}% · costo actual del inventario</p>
        </article>
        <article class="ex-kpi">
            <p class="ex-kpi__label">Inventario</p>
            <p class="ex-kpi__value">C$ {{ number_format($kpis['inventory_value'], 0) }}</p>
            <p class="ex-kpi__meta">{{ number_format($kpis['low_stock']) }} ítems en mínimo</p>
        </article>
        <article class="ex-kpi">
            <p class="ex-kpi__label">Por cobrar</p>
            <p class="ex-kpi__value">C$ {{ number_format($kpis['receivables'], 0) }}</p>
            <p class="ex-kpi__meta">Vencido C$ {{ number_format($kpis['overdue_amount'], 0) }}</p>
        </article>
        <article class="ex-kpi">
            <p class="ex-kpi__label">Mix de cobro</p>
            <p class="ex-kpi__value">{{ number_format($cashShare, 0) }}%</p>
            <p class="ex-kpi__meta">Contado C$ {{ number_format($kpis['cash_month'], 0) }}</p>
        </article>
        <article class="ex-kpi">
            <p class="ex-kpi__label">Nómina neta</p>
            <p class="ex-kpi__value">C$ {{ number_format($kpis['payroll_net'], 0) }}</p>
            <p class="ex-kpi__meta">{{ $kpis['clients'] }} clientes activos</p>
        </article>
        <article class="ex-kpi">
            <p class="ex-kpi__label">Compras del mes</p>
            <p class="ex-kpi__value">C$ {{ number_format($kpis['purchases_month'], 0) }}</p>
            <p class="ex-kpi__meta">Crédito C$ {{ number_format($kpis['credit_month'], 0) }}</p>
        </article>
    </div>

    <div class="ex-charts">
        <div class="ex-panel ex-panel--wide">
            <div class="ex-panel__head">
                <div>
                    <h2>Tendencia comercial</h2>
                    <p>Ventas frente a compras · últimos 6 meses</p>
                </div>
            </div>
            <div class="ex-chart ex-chart--trend">
                <canvas id="execTrendChart"></canvas>
            </div>
        </div>
        <div class="ex-panel">
            <div class="ex-panel__head">
                <div>
                    <h2>Mix de categoría</h2>
                    <p>Participación del mes en curso</p>
                </div>
            </div>
            <div class="ex-donut">
                <canvas id="execCategoryChart"></canvas>
                <div class="ex-donut__center">
                    <strong>{{ number_format($cashShare, 0) }}%</strong>
                    <span>contado</span>
                </div>
            </div>
            <div class="ex-split" aria-label="Mix contado y crédito">
                <div class="ex-split__track">
                    <span style="width: {{ $cashShare }}%"></span>
                </div>
                <div class="ex-split__legend">
                    <span>Contado {{ number_format($cashShare, 0) }}%</span>
                    <span>Crédito {{ number_format($creditShare, 0) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="ex-grid">
        <div class="ex-panel">
            <div class="ex-panel__head">
                <div>
                    <h2>Red de sucursales</h2>
                    <p>Participación comercial de la red Estelí</p>
                </div>
                <a href="{{ route('sucursales.index') }}" class="ex-link">Ver mapa</a>
            </div>
            <ol class="ex-rank">
                @forelse($rankedBranches as $index => $row)
                    <li>
                        <span class="ex-rank__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="ex-rank__body">
                            <div class="ex-rank__row">
                                <a href="{{ route('sucursales.show', $row['branch']) }}">{{ $row['branch']->name }}</a>
                                <strong>C$ {{ number_format($row['sales_month'], 0) }}</strong>
                            </div>
                            <p>{{ $row['branch']->city }} · {{ $row['branch']->type_label }} · {{ $row['employees'] }} personas</p>
                            <div class="ex-bar"><span style="width: {{ min(100, round(($row['sales_month'] / $maxBranchSales) * 100)) }}%"></span></div>
                        </div>
                        <span class="ex-rank__share">{{ $row['share'] }}%</span>
                    </li>
                @empty
                    <li class="ex-empty">Aún no hay sucursales configuradas.</li>
                @endforelse
            </ol>
        </div>

        <div class="ex-panel">
            <div class="ex-panel__head">
                <div>
                    <h2>Top productos</h2>
                    <p>Mayor aporte a la facturación del mes</p>
                </div>
                <a href="{{ route('reportes.index', ['report_type' => 'abc']) }}" class="ex-link">Análisis ABC</a>
            </div>
            <ol class="ex-rank">
                @forelse($top_products as $index => $product)
                    <li>
                        <span class="ex-rank__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="ex-rank__body">
                            <div class="ex-rank__row">
                                <span>{{ $product->name }}</span>
                                <strong>C$ {{ number_format($product->total, 0) }}</strong>
                            </div>
                            <p>{{ $product->code }} · {{ number_format($product->quantity, 0) }} uds</p>
                            <div class="ex-bar"><span style="width: {{ min(100, round(((float) $product->total / $maxProduct) * 100)) }}%"></span></div>
                        </div>
                    </li>
                @empty
                    <li class="ex-empty">Sin ventas en el período.</li>
                @endforelse
            </ol>
        </div>
    </div>

    <div class="ex-grid">
        <div class="ex-panel">
            <div class="ex-panel__head">
                <div>
                    <h2>Clientes ancla</h2>
                    <p>Concentración de compra del mes</p>
                </div>
                <a href="{{ route('reportes.index', ['report_type' => 'top_clients']) }}" class="ex-link">Ver ranking</a>
            </div>
            <ol class="ex-rank">
                @forelse($top_clients as $index => $client)
                    <li>
                        <span class="ex-rank__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="ex-rank__body">
                            <div class="ex-rank__row">
                                <span>{{ $client->name }}</span>
                                <strong>C$ {{ number_format($client->total, 0) }}</strong>
                            </div>
                            <p>{{ number_format($client->tickets) }} tickets</p>
                            <div class="ex-bar is-amber"><span style="width: {{ min(100, round(((float) $client->total / $maxClient) * 100)) }}%"></span></div>
                        </div>
                    </li>
                @empty
                    <li class="ex-empty">Aún no hay clientes con compras en el mes.</li>
                @endforelse
            </ol>
        </div>

        <div class="ex-panel">
            <div class="ex-panel__head">
                <div>
                    <h2>Centros de costo {{ now()->year }}</h2>
                    <p>Débitos acumulados del ejercicio</p>
                </div>
                <a href="{{ route('contabilidad.centros-costo.analytics') }}" class="ex-link">Análisis</a>
            </div>
            <ol class="ex-rank">
                @forelse($cost_centers as $center)
                    <li>
                        <span class="ex-rank__code">{{ $center->code }}</span>
                        <div class="ex-rank__body">
                            <div class="ex-rank__row">
                                <span>{{ $center->name }}</span>
                                <strong>C$ {{ number_format($center->debit, 0) }}</strong>
                            </div>
                            <p>{{ $center->type_label }}</p>
                            <div class="ex-bar is-indigo"><span style="width: {{ min(100, round(((float) $center->debit / $maxCenter) * 100)) }}%"></span></div>
                        </div>
                    </li>
                @empty
                    <li class="ex-empty">Aún no hay movimientos con centro de costo.</li>
                @endforelse
            </ol>
        </div>
    </div>

    <nav class="ex-shortcuts no-print" aria-label="Atajos gerenciales">
        @foreach($shortcuts as $shortcut)
            <a href="{{ $shortcut['href'] }}" class="ex-shortcut">
                <strong>{{ $shortcut['label'] }}</strong>
                <span>{{ $shortcut['text'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const money = (value) => 'C$ ' + Number(value).toLocaleString('es-NI', { maximumFractionDigits: 0 });
        const trend = @json($trend);
        const categories = @json($categories);
        const palette = ['#34d399', '#6366f1', '#f59e0b', '#f43f5e', '#38bdf8', '#a78bfa', '#14b8a6', '#94a3b8'];

        const grid = {
            color: 'rgba(148, 163, 184, 0.18)',
            drawBorder: false,
        };

        new Chart(document.getElementById('execTrendChart'), {
            type: 'line',
            data: {
                labels: trend.map((row) => row.label),
                datasets: [
                    {
                        label: 'Ventas',
                        data: trend.map((row) => row.sales),
                        borderColor: '#059669',
                        backgroundColor: (context) => {
                            const { ctx, chartArea } = context.chart;
                            if (!chartArea) return 'rgba(5,150,105,.16)';
                            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            gradient.addColorStop(0, 'rgba(5,150,105,.28)');
                            gradient.addColorStop(1, 'rgba(5,150,105,0)');
                            return gradient;
                        },
                        fill: true,
                        tension: 0.38,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        borderWidth: 2.5,
                    },
                    {
                        label: 'Compras',
                        data: trend.map((row) => row.purchases),
                        borderColor: '#e11d48',
                        backgroundColor: 'rgba(225,29,72,.08)',
                        fill: true,
                        tension: 0.38,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                        borderDash: [5, 4],
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } },
                    tooltip: { callbacks: { label: (item) => `${item.dataset.label}: ${money(item.parsed.y)}` } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { grid, ticks: { callback: (value) => money(value), color: '#64748b' } },
                },
            },
        });

        new Chart(document.getElementById('execCategoryChart'), {
            type: 'doughnut',
            data: {
                labels: categories.length ? categories.map((row) => row.name) : ['Sin ventas'],
                datasets: [{
                    data: categories.length ? categories.map((row) => Number(row.total)) : [1],
                    backgroundColor: palette,
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 12 } },
                    tooltip: { callbacks: { label: (item) => `${item.label}: ${money(item.parsed)}` } },
                },
            },
        });
    });
</script>
@endpush
