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

const suspiciousAmountThreshold = 1_000_000;
const monetaryFieldNames = new Set([
    'amount', 'amount_received', 'advance_payment', 'base_salary', 'credit', 'credit_limit',
    'debit', 'discount_amount', 'hourly_rate', 'labor_cost', 'monthly_payment', 'opening_amount',
    'price', 'purchase_price', 'sale_price', 'salary', 'unit_price',
]);

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.querySelector('[name="large_amount_confirmed"]')?.value === '1') {
        return;
    }

    let largest = 0;
    for (const [name, rawValue] of new FormData(form).entries()) {
        if (typeof rawValue !== 'string') continue;

        const field = name.replaceAll(']', '').split(/[.[]/).filter(Boolean).at(-1);
        if (!monetaryFieldNames.has(field)) continue;

        const value = Math.abs(Number(rawValue.replaceAll(',', '').replaceAll(' ', '')));
        if (Number.isFinite(value)) largest = Math.max(largest, value);
    }

    if (largest < suspiciousAmountThreshold) return;

    const formatted = new Intl.NumberFormat('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(largest);
    if (!window.confirm(`ALERTA DE MONTO ALTO\n\nSe ingresó C$ ${formatted}.\n\n¿Confirmas que revisaste el valor y deseas continuar?`)) {
        event.preventDefault();
        return;
    }

    const confirmation = document.createElement('input');
    confirmation.type = 'hidden';
    confirmation.name = 'large_amount_confirmed';
    confirmation.value = '1';
    form.appendChild(confirmation);
});
