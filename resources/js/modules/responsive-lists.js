export function registerResponsiveLists() {
    const page = document.body.dataset.responsiveList === '1';
    const media = window.matchMedia('(max-width: 1023px)');
    const states = new WeakMap();
    function prepareFilters() {
        if (!page) return;
        document.querySelectorAll('main form[method="GET"], main form[method="get"]').forEach(form => {
            let details = form.closest('[data-list-filters]');
            if (!details) {
                details = document.createElement('details'); details.dataset.listFilters = '1';
                const summary = document.createElement('summary'); summary.textContent = 'Buscar y filtrar'; summary.className = 'cursor-pointer py-1 text-sm font-semibold text-slate-700';
                form.before(details); details.append(summary, form); form.classList.add('mt-3');
            }
            details.open = !media.matches || [...new URL(location.href).searchParams.keys()].some(key => !['page','per_page'].includes(key));
        });
    }
    function syncPageSize() {
        if (!page) return;
        if ([...document.querySelectorAll('[role="dialog"]')].some(dialog => dialog.getClientRects().length && getComputedStyle(dialog).display !== 'none')) return;
        const url = new URL(location.href), size = media.matches ? 2 : 5;
        const oldSize = Number(url.searchParams.get('per_page') || 5);
        if (oldSize === size) return;
        const first = (Math.max(1, Number(url.searchParams.get('page') || 1)) - 1) * oldSize;
        url.searchParams.set('per_page', size); url.searchParams.set('page', Math.floor(first / size) + 1);
        location.replace(url.href);
    }
    function prepareTables() {
        document.querySelectorAll('main table').forEach(table => {
            if (table.closest('[role="dialog"]') || table.parentElement.classList.contains('hidden')) return;
            table.classList.add('responsive-record-table');
            const headings = [...table.querySelectorAll('thead th')].map(h => h.textContent.trim());
            table.querySelectorAll('tbody > tr').forEach(row => [...row.children].forEach((cell, index) => {
                const label = headings[index] || 'Acciones';
                if (cell.dataset.label !== label) cell.dataset.label = label;
            }));
            const rows = [...table.querySelectorAll('tbody > tr')].filter(row => row.cells.length && !row.querySelector('td[colspan]'));
            const size = media.matches ? 2 : 5;
            let state = states.get(table);
            if (!state) { state = { page: 1, nav: document.createElement('nav'), stamp: '' }; states.set(table, state); state.nav.className = 'flex flex-wrap items-center justify-center gap-2 p-3'; state.nav.setAttribute('aria-label', 'Páginas de registros'); }
            const pages = Math.max(1, Math.ceil(rows.length / size)); state.page = Math.min(state.page, pages);
            rows.forEach((row, index) => row.hidden = index < (state.page - 1) * size || index >= state.page * size);
            const stamp = `${rows.length}.${size}.${state.page}`;
            if (state.stamp === stamp) return; state.stamp = stamp;
            state.nav.replaceChildren();
            if (pages <= 1) { state.nav.remove(); return; }
            const previous = document.createElement('button'), next = document.createElement('button'), label = document.createElement('span');
            previous.type = next.type = 'button'; previous.className = next.className = 'workspace-button'; label.className = 'text-xs text-slate-600';
            previous.textContent = 'Anterior'; next.textContent = 'Siguiente'; label.textContent = `Página ${state.page} de ${pages}`;
            previous.disabled = state.page === 1; next.disabled = state.page === pages;
            previous.onclick = () => { state.page--; prepareTables(); }; next.onclick = () => { state.page++; prepareTables(); };
            state.nav.append(previous, label, next); table.after(state.nav);
        });
    }
    syncPageSize(); media.addEventListener('change', () => { syncPageSize(); prepareFilters(); prepareTables(); }); prepareFilters(); prepareTables();
    let scheduled = false;
    new MutationObserver(() => {
        if (scheduled) return; scheduled = true;
        requestAnimationFrame(() => { scheduled = false; prepareTables(); });
    }).observe(document.querySelector('main') || document.body, { childList: true, subtree: true });
}
