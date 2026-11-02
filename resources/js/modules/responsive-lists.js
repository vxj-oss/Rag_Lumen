export const MOBILE_LIST_QUERY = '(max-width: 1023px)';

export function listWindow(total, page, size) {
    const pages = Math.max(1, Math.ceil(total / size));
    const current = Math.min(pages, Math.max(1, page));
    return { page: current, pages, start: (current - 1) * size, end: current * size };
}

export function resizeListUrl(href, oldSize, size) {
    const url = new URL(href);
    const names = new Set(['page', ...[...url.searchParams.keys()].filter(key => key !== 'per_page' && key.endsWith('_page'))]);
    for (const name of names) {
        if (!url.searchParams.has(name)) continue;
        const first = (Math.max(1, Number(url.searchParams.get(name)) || 1) - 1) * oldSize;
        url.searchParams.set(name, Math.floor(first / size) + 1);
    }
    url.searchParams.set('per_page', size);
    return url.href;
}

export function registerResponsiveLists() {
    const page = document.body.dataset.responsiveList === '1';
    const media = window.matchMedia(MOBILE_LIST_QUERY);
    const states = new WeakMap();
    function prepareFilters() {
        if (!page) return;
        document.querySelectorAll('main form[method="GET"], main form[method="get"]').forEach(form => {
            let details = form.closest('[data-list-filters]');
            if (details) return;
            details = document.createElement('details'); details.dataset.listFilters = '1';
            const summary = document.createElement('summary'); summary.textContent = 'Buscar y filtrar'; summary.className = 'cursor-pointer py-1 text-sm font-semibold text-slate-700';
            form.before(details); details.append(summary, form); form.classList.add('mt-3');
            details.open = !media.matches || [...new URL(location.href).searchParams.keys()].some(key => key !== 'per_page' && key !== 'page' && !key.endsWith('_page'));
        });
    }
    function syncPageSize() {
        const size = media.matches ? 2 : 5;
        document.cookie = `workspace_list_size=${size}; Path=/; Max-Age=31536000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
        if (!page) return;
        if ([...document.querySelectorAll('[role="dialog"]')].some(dialog => dialog.getClientRects().length && getComputedStyle(dialog).display !== 'none')) return;
        const oldSize = Number(document.body.dataset.listSize || 5);
        if (oldSize === size) return;
        location.replace(resizeListUrl(location.href, oldSize, size));
    }
    function prepareTables() {
        document.querySelectorAll('main table, [role="dialog"] table').forEach(table => {
            // These tables have dedicated mobile cards in the same view.
            if (table.closest('.hidden.lg\\:block')) return;
            table.classList.add('responsive-record-table');
            const headings = [...table.querySelectorAll(':scope > thead > tr > th')].map(h => h.textContent.trim());
            const rows = [...table.querySelectorAll(':scope > tbody > tr')].filter(row => row.cells.length && !(row.cells.length === 1 && row.cells[0].colSpan > 1));
            rows.forEach(row => [...row.cells].forEach((cell, index) => {
                const label = headings[index] || cell.dataset.label || 'Acciones';
                if (cell.dataset.label !== label) cell.dataset.label = label;
            }));
            const size = media.matches ? 2 : 5;
            let state = states.get(table);
            if (!state) {
                state = { page: 1, nav: document.createElement('nav'), stamp: '', firstRow: null, size };
                states.set(table, state); state.nav.className = 'workspace-list-pagination flex flex-wrap items-center justify-center gap-2 p-3';
                state.nav.setAttribute('aria-label', table.caption?.textContent.trim() ? `Páginas: ${table.caption.textContent.trim()}` : 'Páginas de registros');
            }
            if (state.firstRow !== rows[0]) { state.page = 1; state.firstRow = rows[0]; }
            if (state.size !== size) { state.page = Math.floor((state.page - 1) * state.size / size) + 1; state.size = size; }
            const range = listWindow(rows.length, state.page, size); state.page = range.page;
            rows.forEach((row, index) => row.hidden = index < range.start || index >= range.end);
            const stamp = `${rows.length}.${size}.${state.page}`;
            if (state.stamp === stamp) return; state.stamp = stamp;
            state.nav.replaceChildren();
            if (range.pages <= 1) { state.nav.remove(); return; }
            const previous = document.createElement('button'), next = document.createElement('button'), label = document.createElement('span');
            previous.type = next.type = 'button'; previous.className = next.className = 'workspace-button'; label.className = 'text-xs text-slate-600';
            label.setAttribute('aria-live', 'polite');
            previous.textContent = 'Anterior'; next.textContent = 'Siguiente'; label.textContent = `Página ${range.page} de ${range.pages}`;
            previous.disabled = range.page === 1; next.disabled = range.page === range.pages;
            previous.onclick = () => { state.page--; prepareTables(); }; next.onclick = () => { state.page++; prepareTables(); };
            state.nav.append(previous, label, next); table.after(state.nav);
        });
    }
    syncPageSize();
    const onResize = () => { syncPageSize(); prepareTables(); };
    media.addEventListener('change', onResize); prepareFilters(); prepareTables();
    let scheduled = false;
    const observer = new MutationObserver(() => {
        if (scheduled) return; scheduled = true;
        requestAnimationFrame(() => { scheduled = false; prepareTables(); });
    });
    observer.observe(document.querySelector('main') || document.body, { childList: true, subtree: true });
    window.addEventListener('pagehide', () => { observer.disconnect(); media.removeEventListener('change', onResize); }, { once: true });
}
