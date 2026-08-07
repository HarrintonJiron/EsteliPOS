import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#64748b';

Chart.register({
    id: 'emptyState',
    afterDraw(chart) {
        const values = chart.data.datasets.flatMap((dataset) => dataset.data ?? []);
        const hasData = values.some((value) => Number.isFinite(Number(value)) && Number(value) !== 0);

        if (hasData) {
            return;
        }

        const { ctx, chartArea } = chart;

        if (!chartArea) {
            return;
        }

        ctx.save();
        ctx.fillStyle = '#94a3b8';
        ctx.font = "500 13px 'Inter', sans-serif";
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('Sin datos para este período', (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
        ctx.restore();
    },
});

window.dispatchEvent(new CustomEvent('charts:ready'));
