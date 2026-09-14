<script>
const initializeDynamicPayrollCharts = () => {
(function () {
    const money = (v) => 'C$ ' + Number(v).toLocaleString('es-NI', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    const moneyFull = (v) => 'C$ ' + Number(v).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const chartsUrl = @json($chartsUrl);
    const monthInput = document.getElementById(@json($monthInputId ?? 'payrollMonthFilter'));
    if (!monthInput || !chartsUrl) {
        return;
    }

    const mode = @json($mode ?? 'nomina');
    let chartInstances = {};

    const destroyChart = (key) => {
        if (chartInstances[key]) {
            chartInstances[key].destroy();
            delete chartInstances[key];
        }
    };

    const renderNominaCharts = (data) => {
        const salaryByEmployee = data.charts.salary_by_employee;
        const deductionBreakdown = data.charts.deduction_breakdown;
        const trend = data.trend;

        destroyChart('employee');
        destroyChart('deductions');
        destroyChart('trend');

        chartInstances.employee = new Chart(document.getElementById('employeePayrollChart'), {
            type: 'bar',
            data: {
                labels: salaryByEmployee.map(i => i.name),
                datasets: [
                    { label: 'Bruto', data: salaryByEmployee.map(i => i.gross), backgroundColor: '#818cf8', borderRadius: 6, maxBarThickness: 28 },
                    { label: 'Neto', data: salaryByEmployee.map(i => i.net), backgroundColor: '#34d399', borderRadius: 6, maxBarThickness: 28 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${money(ctx.raw)}` } } },
                scales: {
                    y: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } },
                    x: { grid: { display: false }, ticks: { maxRotation: 45, minRotation: 0 } },
                },
            },
        });

        chartInstances.deductions = new Chart(document.getElementById('nominaDeductionsChart'), {
            type: 'doughnut',
            data: {
                labels: deductionBreakdown.map(i => i.label),
                datasets: [{ data: deductionBreakdown.map(i => i.value), backgroundColor: ['#4f46e5', '#f59e0b', '#f43f5e', '#0ea5e9'], borderWidth: 0, hoverOffset: 6 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${money(ctx.raw)}` } } },
                cutout: '60%',
            },
        });

        chartInstances.trend = new Chart(document.getElementById('nominaTrendChart'), {
            type: 'line',
            data: {
                labels: trend.map(i => i.label),
                datasets: [
                    { label: 'Bruto', data: trend.map(i => i.gross), borderColor: '#4f46e5', tension: 0.35, fill: false },
                    { label: 'Neto', data: trend.map(i => i.net), borderColor: '#059669', tension: 0.35, fill: false },
                    { label: 'Deducciones', data: trend.map(i => i.deductions), borderColor: '#e11d48', tension: 0.35, fill: false },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } },
                    x: { grid: { display: false } },
                },
            },
        });
    };

    const renderDashboardCharts = (data) => {
        const trend = data.trend;
        const deductionBreakdown = data.charts.deduction_breakdown;
        const salaryByEmployee = data.charts.salary_by_employee;
        const contractDistribution = data.charts.contract_distribution;

        destroyChart('trend');
        destroyChart('deductions');
        destroyChart('salary');
        destroyChart('contract');

        chartInstances.trend = new Chart(document.getElementById('payrollTrendChart'), {
            type: 'line',
            data: {
                labels: trend.map(i => i.label),
                datasets: [
                    { label: 'Bruto', data: trend.map(i => i.gross), borderColor: '#4f46e5', backgroundColor: 'rgba(79, 70, 229, 0.12)', fill: true, tension: 0.35 },
                    { label: 'Neto', data: trend.map(i => i.net), borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.08)', fill: true, tension: 0.35 },
                    { label: 'Deducciones', data: trend.map(i => i.deductions), borderColor: '#e11d48', tension: 0.35 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } },
                    x: { grid: { display: false } },
                },
            },
        });

        chartInstances.deductions = new Chart(document.getElementById('deductionsChart'), {
            type: 'doughnut',
            data: {
                labels: deductionBreakdown.map(i => i.label),
                datasets: [{ data: deductionBreakdown.map(i => i.value), backgroundColor: ['#4f46e5', '#f59e0b', '#f43f5e', '#0ea5e9'], borderWidth: 0, hoverOffset: 6 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${money(ctx.raw)}` } } },
                cutout: '62%',
            },
        });

        chartInstances.salary = new Chart(document.getElementById('salaryByEmployeeChart'), {
            type: 'bar',
            data: {
                labels: salaryByEmployee.map(i => i.name),
                datasets: [{ label: 'Neto', data: salaryByEmployee.map(i => i.net), backgroundColor: '#6366f1', borderRadius: 8, maxBarThickness: 36 }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => money(ctx.raw) } } },
                scales: {
                    x: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } },
                    y: { grid: { display: false } },
                },
            },
        });

        chartInstances.contract = new Chart(document.getElementById('contractChart'), {
            type: 'doughnut',
            data: {
                labels: contractDistribution.map(i => i.label),
                datasets: [{ data: contractDistribution.map(i => i.value), backgroundColor: ['#0f766e', '#2563eb', '#d97706', '#7c3aed', '#64748b'], borderWidth: 0 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } },
                cutout: '55%',
            },
        });
    };

    const updateNominaSummary = (data) => {
        const totals = data.payrollReport.totals;
        document.querySelectorAll('[data-payroll-total]').forEach((el) => {
            const key = el.dataset.payrollTotal;
            if (key === 'statutory_deductions') {
                el.textContent = moneyFull(Number(totals.inss_deduction) + Number(totals.ir_deduction));
                return;
            }
            if (totals[key] !== undefined) {
                el.textContent = moneyFull(totals[key]);
            }
        });

        const subtitle = document.getElementById('payrollPageSubtitle');
        if (subtitle) {
            let status = data.isPaid
                ? ' · Pagada' + (data.paidAt ? ` el ${data.paidAt}` : '') + (data.paidByName ? ` por ${data.paidByName}` : '')
                : ' · Pendiente de pago';
            subtitle.innerHTML = `${data.periodStart} – ${data.periodEnd} · ${data.employeeCount} empleados<span class="${data.isPaid ? 'text-emerald-700' : 'text-amber-700'} font-medium">${status}</span>`;
        }

        const title = document.getElementById('payrollPageTitle');
        if (title) {
            title.textContent = `Nómina · ${data.periodLabel}`;
        }
    };

    const updateDashboardSummary = (data) => {
        const totals = data.totals;
        const stats = data.stats;

        const map = {
            'net-salary': totals.net_salary,
            'gross-salary': totals.gross_salary,
            'total-deductions': totals.total_deductions,
            'bonuses': totals.bonuses,
            'other-deductions': totals.other_deductions,
            'pending-total': stats.pending_total,
            'active-employees': stats.active_employees,
            'inactive-employees': stats.inactive_employees,
            'total-employees': stats.total_employees,
            'pending-leaves': stats.pending_leaves,
            'pending-loans': stats.pending_loans,
            'pending-bonuses': stats.pending_bonuses,
            'pending-deductions': stats.pending_deductions,
            'active-loans-count': stats.active_loans_count,
            'active-loans-balance': stats.active_loans_balance,
        };

        document.querySelectorAll('[data-dashboard-stat]').forEach((el) => {
            const key = el.dataset.dashboardStat;
            if (map[key] === undefined) {
                return;
            }
            el.textContent = typeof map[key] === 'number' && (key.includes('salary') || key.includes('deductions') || key.includes('bonuses') || key.includes('balance'))
                ? moneyFull(map[key])
                : String(map[key]);
        });

        const subtitle = document.getElementById('dashboardPageSubtitle');
        if (subtitle) {
            subtitle.textContent = `Resumen de nómina, personal y pendientes · ${data.periodLabel}`;
        }
    };

    const renderCharts = (data) => {
        if (mode === 'dashboard') {
            renderDashboardCharts(data);
            updateDashboardSummary(data);
        } else {
            renderNominaCharts(data);
            updateNominaSummary(data);
        }
    };

    const initialData = @json($initialChartData);
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = '#64748b';
    renderCharts(initialData);

    let fetchTimer = null;
    const refreshCharts = async (month) => {
        const url = new URL(chartsUrl, window.location.origin);
        url.searchParams.set('month', month);

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            throw new Error(`No se pudieron cargar los gráficos (${response.status})`);
        }

        const data = await response.json();
        renderCharts(data);

        const pageUrl = new URL(window.location.href);
        pageUrl.searchParams.set('month', month);
        window.history.replaceState(null, '', pageUrl.toString());
    };

    monthInput.addEventListener('change', () => {
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(() => {
            refreshCharts(monthInput.value).catch(error => console.error(error));
        }, 250);
    });

    monthInput.closest('form')?.addEventListener('submit', event => {
        event.preventDefault();
        refreshCharts(monthInput.value).catch(error => console.error(error));
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            refreshCharts(monthInput.value).catch(error => console.error(error));
        }
    });
})();
};

if (window.Chart) {
    initializeDynamicPayrollCharts();
} else {
    window.addEventListener('charts:ready', initializeDynamicPayrollCharts, { once: true });
}
</script>
