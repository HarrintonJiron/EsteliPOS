import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;
window.dispatchEvent(new CustomEvent('charts:ready'));

const compactTableHeading = /^(#(?:\s+factura)?|id|c[oó]digo|n[uú]mero|factura|fecha|vence|stock(?:\s+(?:antes|despu[eé]s|inicial))?|cantidad|cant\.?|precio|costo|subtotal|iva|impuesto|total|saldo|margen|rot\.?|vend\.?|estado(?:\s+.+)?|acciones?|opciones?|pago|condici[oó]n|tipo)$/i;

function compactApplicationTableColumns(root = document) {
    const tables = [];

    if (root instanceof HTMLTableElement) tables.push(root);
    if (root instanceof Element && root.closest('main table')) tables.push(root.closest('main table'));
    if (root.querySelectorAll) {
        tables.push(...Array.from(root.querySelectorAll('table')).filter(table => table.closest('main')));
    }

    [...new Set(tables)].forEach(table => {
        const headingRow = table.querySelector('thead tr:not(.ui-data-grid-filters)');
        if (!headingRow) return;

        Array.from(headingRow.cells).forEach((heading, columnIndex) => {
            const label = heading.textContent.replace(/\s+/g, ' ').trim();
            const isCompact = compactTableHeading.test(label)
                || heading.classList.contains('text-right')
                || heading.classList.contains('text-center');

            if (!isCompact) return;

            heading.classList.add('table-col-compact');
            table.querySelector('thead tr.ui-data-grid-filters')?.cells[columnIndex]?.classList.add('table-col-compact');
            table.querySelectorAll('tbody tr').forEach(row => {
                row.cells[columnIndex]?.classList.add('table-col-compact');
            });
        });

    });
}

document.addEventListener('DOMContentLoaded', () => {
    compactApplicationTableColumns();

    new MutationObserver(mutations => {
        mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
            if (node instanceof Element) compactApplicationTableColumns(node);
        }));
    }).observe(document.body, { childList: true, subtree: true });
});
