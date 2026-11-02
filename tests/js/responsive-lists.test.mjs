import test from 'node:test';
import assert from 'node:assert/strict';
import { listWindow, resizeListUrl } from '../../resources/js/modules/responsive-lists.js';

test('mobile pagination reaches every record with two cards per page, including a short final page', () => {
    const records = Array.from({ length: 7 }, (_, i) => i + 1);
    const seen = [];
    for (let page = 1; page <= 4; page++) {
        const range = listWindow(records.length, page, 2);
        const visible = records.slice(range.start, range.end);
        assert.ok(visible.length <= 2);
        seen.push(...visible);
    }
    assert.deepEqual(seen, records);
    assert.deepEqual(listWindow(0, 1, 2), { page: 1, pages: 1, start: 0, end: 2 });
    assert.equal(listWindow(1, 4, 2).page, 1);
});

test('desktop shows five records and clamps pagination when filtering removes pages', () => {
    assert.deepEqual(listWindow(12, 2, 5), { page: 2, pages: 3, start: 5, end: 10 });
    assert.deepEqual(listWindow(3, 8, 5), { page: 1, pages: 1, start: 0, end: 5 });
});

test('switching to mobile preserves filters, section and independent area pagination positions', () => {
    const url = new URL(resizeListUrl('https://example.test/workspaces/software?members_page=3&activities_page=2&projects_page=1&search=Ana#equipo', 5, 2));
    assert.equal(url.searchParams.get('members_page'), '6');
    assert.equal(url.searchParams.get('activities_page'), '3');
    assert.equal(url.searchParams.get('projects_page'), '1');
    assert.equal(url.searchParams.get('search'), 'Ana');
    assert.equal(url.hash, '#equipo');
    assert.equal(url.searchParams.get('per_page'), '2');
    assert.equal(url.searchParams.has('page'), false);
});

test('switching to desktop retains the first visible record and project tab', () => {
    const url = new URL(resizeListUrl('https://example.test/projects/1?tab=documents&documents_page=4&per_page=2', 2, 5));
    assert.equal(url.searchParams.get('documents_page'), '2');
    assert.equal(url.searchParams.get('tab'), 'documents');
    assert.equal(url.searchParams.get('per_page'), '5');
});

test('live tables become labelled cards, paginate independently and reset when their records change', async () => {
    const { registerResponsiveLists } = await import('../../resources/js/modules/responsive-lists.js');
    const saved = Object.fromEntries(['window','document','location','MutationObserver','requestAnimationFrame'].map(key => [key, globalThis[key]]));
    const element = () => ({ children: [], className: '', textContent: '', attributes: {},
        setAttribute(key, value) { this.attributes[key] = value; },
        append(...items) { this.children.push(...items); }, replaceChildren() { this.children = []; }, remove() { this.removed = true; } });
    const makeRows = n => Array.from({ length: n }, () => ({ hidden: false, cells: [{ dataset: {}, colSpan: 1 }, { dataset: {}, colSpan: 1 }] }));
    const table = { rows: makeRows(7), caption: { textContent: 'Equipo' }, classList: { add(value) { table.appliedClass = value; } },
        closest() { return null; }, querySelectorAll(selector) { return selector.includes('thead') ? [{ textContent: 'Nombre' }, { textContent: '' }] : this.rows; }, after(nav) { this.nav = nav; } };
    const media = { matches: true, addEventListener(_, fn) { this.listener = fn; }, removeEventListener() {} };
    let observeChanges;
    try {
        globalThis.window = { matchMedia: () => media, addEventListener() {} };
        globalThis.location = { href: 'https://example.test/dashboard', protocol: 'https:' };
        globalThis.document = { body: { dataset: {} }, querySelectorAll: () => [table], querySelector: () => ({}), createElement: element };
        globalThis.MutationObserver = class { constructor(fn) { observeChanges = fn; } observe() {} disconnect() {} };
        globalThis.requestAnimationFrame = fn => fn();
        registerResponsiveLists();
        assert.equal(table.appliedClass, 'responsive-record-table');
        assert.equal(table.rows.filter(row => !row.hidden).length, 2);
        assert.equal(table.rows[0].cells[0].dataset.label, 'Nombre');
        assert.equal(table.rows[0].cells[1].dataset.label, 'Acciones');
        assert.equal(table.nav.children[1].textContent, 'Página 1 de 4');
        assert.equal(table.nav.children[0].disabled, true);
        table.nav.children[2].onclick();
        assert.equal(table.rows[0].hidden, true);
        assert.equal(table.rows[2].hidden, false);
        media.matches = false; media.listener();
        assert.equal(table.rows.filter(row => !row.hidden).length, 5);
        assert.equal(table.nav.children[1].textContent, 'Página 1 de 2');
        table.nav.children[2].onclick();
        table.rows = makeRows(1); observeChanges();
        assert.equal(table.rows[0].hidden, false);
        assert.equal(table.nav.removed, true);
    } finally {
        for (const [key, value] of Object.entries(saved)) { if (value === undefined) delete globalThis[key]; else globalThis[key] = value; }
    }
});
