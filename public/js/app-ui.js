document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('ui-toast-container');

    if (container) {
        document.querySelectorAll('[data-ui-toast]').forEach((el) => {
            const type = el.dataset.uiToast || 'info';
            const message = el.textContent.trim();
            if (message) showToast(message, type);
            el.remove();
        });
    }

    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[type="submit"]');
            if (btn && !btn.disabled) {
                btn.dataset.originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="inline-block animate-pulse">Procesando...</span>';
            }
        });
    });

    document.querySelectorAll('main table').forEach((table) => {
        if (
            table.dataset.responsive === 'false'
            || table.closest('.responsive-table, .data-card-body, .overflow-x-auto')
        ) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'responsive-table';
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', table.getAttribute('aria-label') || 'Tabla desplazable');
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });

    initializeDataGrids();

    const dataGridObserver = new MutationObserver((mutations) => {
        if (mutations.some((mutation) => Array.from(mutation.addedNodes).some((node) => node.nodeType === Node.ELEMENT_NODE && (node.matches?.('table') || node.querySelector?.('table'))))) {
            initializeDataGrids();
        }
    });
    dataGridObserver.observe(document.querySelector('main') || document.body, { childList: true, subtree: true });

    initializeQuickUserSwitch();
});

function initializeQuickUserSwitch() {
    const modal = document.getElementById('quick-user-switch-modal');
    const openButton = document.getElementById('quick-user-switch-open');
    if (!modal || !openButton) return;

    const closeButton = document.getElementById('quick-user-switch-close');
    const userSelect = document.getElementById('quick-switch-user-id');
    const pinInput = document.getElementById('quick-switch-pin');
    const show = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        (userSelect?.value ? pinInput : userSelect)?.focus();
    };
    const hide = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        pinInput.value = '';
        openButton.focus();
    };

    openButton.addEventListener('click', show);
    closeButton?.addEventListener('click', hide);
    modal.querySelector('[data-quick-switch-cancel]')?.addEventListener('click', hide);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) hide();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) hide();
    });
    userSelect?.addEventListener('change', () => pinInput?.focus());

    if (modal.querySelector('[data-switch-error]')) show();
}

function initializeDataGrids() {
    document.querySelectorAll('main table').forEach((table) => {
        if (table.dataset.gridInitialized === 'true' || !isDataGridCandidate(table)) return;

        const body = table.tBodies[0];
        const headers = Array.from(table.tHead.rows[0].cells);
        const originalRows = Array.from(body.rows);
        if (!originalRows.length) return;

        const columnTypes = headers.map((_, index) => detectColumnType(originalRows, index));
        const state = {
            filters: headers.map(() => ''),
            page: 1,
            pageSize: 35,
            sortIndex: defaultSortIndex(headers, columnTypes),
            sortDirection: 'asc',
        };

        if (state.sortIndex >= 0 && columnTypes[state.sortIndex] === 'date') {
            state.sortDirection = 'desc';
        }

        table.classList.add('ui-data-grid');
        table.dataset.gridInitialized = 'true';
        const filterRow = table.tHead.insertRow(-1);
        filterRow.className = 'ui-data-grid-filters';

        headers.forEach((header, index) => {
            const label = header.textContent.trim();
            const isActions = /acci[oó]n|opci[oó]n/i.test(label);
            header.tabIndex = isActions ? -1 : 0;
            header.classList.toggle('ui-data-grid-sortable', !isActions);

            if (!isActions) {
                const sort = () => {
                    const sameColumn = state.sortIndex === index;
                    state.sortIndex = index;
                    state.sortDirection = sameColumn
                        ? (state.sortDirection === 'asc' ? 'desc' : 'asc')
                        : (columnTypes[index] === 'date' ? 'desc' : 'asc');
                    state.page = 1;
                    renderDataGrid();
                };
                header.addEventListener('click', sort);
                header.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        sort();
                    }
                });
            }

            const cell = document.createElement('th');
            if (!isActions) {
                const input = document.createElement('input');
                input.type = columnTypes[index] === 'date' ? 'date' : 'search';
                input.className = 'ui-data-grid-filter';
                input.placeholder = `Filtrar ${label}`;
                input.setAttribute('aria-label', `Filtrar columna ${label}`);
                input.addEventListener('input', () => {
                    state.filters[index] = normalizeText(input.value);
                    state.page = 1;
                    renderDataGrid();
                });
                input.addEventListener('click', (event) => event.stopPropagation());
                cell.appendChild(input);
            }
            filterRow.appendChild(cell);
        });

        const controls = document.createElement('div');
        controls.className = 'ui-data-grid-controls';
        controls.innerHTML = '<span class="ui-data-grid-count"></span><div class="ui-data-grid-pages"><button type="button" data-grid-prev aria-label="Página anterior">Anterior</button><span data-grid-page></span><button type="button" data-grid-next aria-label="Página siguiente">Siguiente</button></div>';
        const host = table.closest('.responsive-table, .overflow-x-auto, .data-card-body') || table.parentElement;
        host.insertAdjacentElement('afterend', controls);

        controls.querySelector('[data-grid-prev]').addEventListener('click', () => {
            if (state.page > 1) {
                state.page -= 1;
                renderDataGrid();
            }
        });
        controls.querySelector('[data-grid-next]').addEventListener('click', () => {
            state.page += 1;
            renderDataGrid();
        });

        function renderDataGrid() {
            const rows = originalRows.filter((row) => state.filters.every((filter, index) => {
                if (!filter) return true;
                if (columnTypes[index] === 'date') {
                    return gridDateKey(cellValue(row, index)) === filter;
                }
                return normalizeText(cellValue(row, index)).includes(filter);
            }));

            if (state.sortIndex >= 0) {
                rows.sort((left, right) => compareGridValues(
                    cellValue(left, state.sortIndex),
                    cellValue(right, state.sortIndex),
                    columnTypes[state.sortIndex],
                    state.sortDirection,
                ));
            }

            const pages = Math.max(1, Math.ceil(rows.length / state.pageSize));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * state.pageSize;
            const visibleRows = new Set(rows.slice(start, start + state.pageSize));

            rows.concat(originalRows.filter((row) => !rows.includes(row))).forEach((row) => body.appendChild(row));

            originalRows.forEach((row) => {
                row.hidden = !visibleRows.has(row);
            });

            headers.forEach((header, index) => {
                header.removeAttribute('aria-sort');
                if (index === state.sortIndex) {
                    header.setAttribute('aria-sort', state.sortDirection === 'asc' ? 'ascending' : 'descending');
                }
            });

            controls.querySelector('.ui-data-grid-count').textContent = `${rows.length} fila${rows.length === 1 ? '' : 's'} · máximo 35 por página`;
            controls.querySelector('[data-grid-page]').textContent = `Página ${state.page} de ${pages}`;
            controls.querySelector('[data-grid-prev]').disabled = state.page <= 1;
            controls.querySelector('[data-grid-next]').disabled = state.page >= pages;
            controls.hidden = originalRows.length <= state.pageSize && state.filters.every((filter) => !filter);
        }

        renderDataGrid();
    });
}

function isDataGridCandidate(table) {
    if (table.dataset.grid === 'false' || !table.tHead || !table.tBodies.length) return false;
    if (table.closest('[data-no-grid], form, .print-only')) return false;
    if (table.id && /itemsTable|linesTable|bulkTable/i.test(table.id)) return false;

    const headers = Array.from(table.tHead.rows[0]?.cells || []);
    return headers.length > 1 && headers.some((header) => header.textContent.trim());
}

function cellValue(row, index) {
    const cell = row.cells[index];
    return cell?.dataset.sortValue || cell?.textContent.trim() || '';
}

function normalizeText(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('es');
}

function parseGridDate(value) {
    const text = String(value ?? '').trim();
    const iso = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (iso) return Date.UTC(Number(iso[1]), Number(iso[2]) - 1, Number(iso[3]));

    const local = text.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/);
    if (local) return Date.UTC(Number(local[3]), Number(local[2]) - 1, Number(local[1]));

    return Number.NaN;
}

function gridDateKey(value) {
    const timestamp = parseGridDate(value);
    if (Number.isNaN(timestamp)) return '';
    return new Date(timestamp).toISOString().slice(0, 10);
}

function detectColumnType(rows, index) {
    const values = rows.map((row) => cellValue(row, index)).filter(Boolean).slice(0, 20);
    if (values.length && values.every((value) => !Number.isNaN(parseGridDate(value)))) return 'date';
    if (values.length && values.every((value) => /^[-+]?\s*(?:C\$|US\$|\$)?\s*[\d.,]+\s*%?$/.test(value))) return 'number';
    return 'text';
}

function defaultSortIndex(headers, types) {
    const dateIndex = types.findIndex((type) => type === 'date');
    if (dateIndex >= 0) return dateIndex;

    return headers.findIndex((header) => !/acci[oó]n|opci[oó]n/i.test(header.textContent));
}

function compareGridValues(left, right, type, direction) {
    let result;
    if (type === 'date') {
        result = parseGridDate(left) - parseGridDate(right);
    } else if (type === 'number') {
        const number = (value) => Number(String(value).replace(/[^\d,.-]/g, '').replace(/,(?=\d{1,2}$)/, '.').replace(/,/g, ''));
        result = number(left) - number(right);
    } else {
        result = normalizeText(left).localeCompare(normalizeText(right), 'es', { numeric: true, sensitivity: 'base' });
    }

    return direction === 'asc' ? result : -result;
}

function showToast(message, type = 'info', duration = 5000) {
    let container = document.getElementById('ui-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'ui-toast-container';
        container.className = 'ui-toast-container';
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `ui-alert ui-alert-${type} ui-toast`;
    const text = document.createElement('span');
    text.textContent = String(message ?? '');
    toast.appendChild(text);
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(12px)';
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

window.showToast = showToast;

// Sustituye únicamente los alert() nativos por mensajes internos. Los flujos
// confirm() y prompt() conservan su comportamiento actual.
window.alert = function (message) {
    const text = String(message ?? '');
    const type = /error|inválid|insuficiente|no se pudo|no hay|vacío|vacía|sin stock/i.test(text)
        ? 'error'
        : (/advertencia|stock|selecciona|ingresa|por favor/i.test(text) ? 'warning' : 'success');

    showToast(text, type, 6000);
};
