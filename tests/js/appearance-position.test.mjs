import test from 'node:test';
import assert from 'node:assert/strict';
import { createAppearance } from '../../resources/js/modules/appearance.js';
import { clampPosition, panelBounds } from '../../resources/js/modules/assistant-position.js';
import { registerAssistant } from '../../resources/js/modules/assistant.js';

function appearance(saved = null, dark = false) {
    const values = new Map([['workspace.theme', saved]]), handlers = {};
    const media = { matches: dark, addEventListener: (_, fn) => handlers.media = fn, removeEventListener: () => delete handlers.media };
    const events = { addEventListener: (_, fn) => handlers.storage = fn, removeEventListener: () => delete handlers.storage };
    const root = { dataset: {}, style: {} };
    const state = createAppearance({ root, media, events, storage: { getItem: key => values.get(key), setItem: (key, value) => values.set(key, value) } });
    state.init(); return { state, root, media, handlers, values };
}
test('theme follows device until an explicit preference is selected and saved', () => {
    const { state, root, media, handlers, values } = appearance(null, true);
    assert.equal(root.dataset.theme, 'dark');
    state.setMode('light'); assert.equal(root.dataset.theme, 'light'); assert.equal(values.get('workspace.theme'), 'light');
    media.matches = true; handlers.media(); assert.equal(root.dataset.theme, 'light');
    state.setMode('system'); assert.equal(root.dataset.theme, 'dark');
    media.matches = false; handlers.media(); assert.equal(root.dataset.theme, 'light');
});
test('theme restores saved preference, handles cross-tab changes and removes listeners', () => {
    const { state, root, handlers } = appearance('dark', false);
    assert.equal(root.dataset.theme, 'dark');
    handlers.storage({ key: 'workspace.theme', newValue: 'light' }); assert.equal(state.mode, 'light');
    state.setMode('unknown'); assert.equal(root.dataset.theme, 'light');
    handlers.storage({ key: 'workspace.theme', newValue: null }); assert.equal(state.mode, 'system');
    state.destroy(); assert.equal(handlers.media, undefined); assert.equal(handlers.storage, undefined);
});

test('one click switches from the visible system theme and persists the opposite theme', () => {
    const { state, root, values, handlers, media } = appearance('system', true);
    state.toggle(); assert.equal(state.mode, 'light'); assert.equal(root.dataset.theme, 'light');
    assert.equal(values.get('workspace.theme'), 'light');
    media.matches = true; handlers.media(); assert.equal(root.dataset.theme, 'light');
    state.toggle(); assert.equal(root.dataset.theme, 'dark'); assert.equal(values.get('workspace.theme'), 'dark');
});
test('theme remains usable when storage is unavailable', () => {
    const state = createAppearance({ root: { dataset: {}, style: {} }, media: { matches: false, addEventListener() {} }, events: { addEventListener() {} }, storage: { getItem() { throw Error(); }, setItem() { throw Error(); } } });
    state.init(); state.setMode('dark'); assert.equal(state.resolved, 'dark');
});
test('launcher positions stay visible after dragging beyond screen edges or resizing', () => {
    assert.deepEqual(clampPosition({ x: -100, y: 9999 }, { width: 360, height: 640 }), { x: 12, y: 572 });
    assert.deepEqual(clampPosition({ x: NaN, y: Infinity }, { width: 360, height: 640 }), { x: 292, y: 572 });
    assert.deepEqual(clampPosition({ x: 1600, y: 900 }, { width: 320, height: 480 }), { x: 252, y: 412 });
});
test('panel opens on the side with room, remains visible and does not cover the launcher', () => {
    for (const viewport of [{ width: 360, height: 640 }, { width: 1440, height: 900 }, { width: 320, height: 300 }]) {
        for (const y of [12, viewport.height / 2, viewport.height - 68]) {
            const pos = clampPosition({ x: viewport.width / 2, y }, viewport), box = panelBounds(pos, viewport);
            assert.ok(box.x >= 12 && box.x + box.width <= viewport.width - 12);
            assert.ok(box.y >= 12 && box.y + box.height <= viewport.height - 12);
            assert.ok(box.y + box.height <= pos.y || box.y >= pos.y + 56);
        }
    }
});
function agent() {
    let factory; registerAssistant({ data: (_, fn) => factory = fn });
    const listeners = {};
    globalThis.window = { innerWidth: 360, innerHeight: 640, addEventListener: (name, fn) => listeners[name] = fn, matchMedia: () => ({ matches: true }) };
    const saved = new Map(); globalThis.localStorage = { getItem: key => saved.get(key), setItem: (key, value) => saved.set(key, value) };
    const c = factory({ userId: 7 }); const target = { setPointerCapture() {}, hasPointerCapture() { return false; } };
    c.$refs = { launcher: target }; c.floatingPosition = { x: 200, y: 500 };
    return { c, target, saved, listeners };
}
test('pointer drag persists position without opening chat accidentally', async () => {
    const { c, target, saved } = agent();
    c.beginDrag({ pointerId: 1, button: 0, clientX: 210, clientY: 510, currentTarget: target });
    c.dragTo({ pointerId: 1, clientX: 212, clientY: 512, preventDefault() {} }); assert.deepEqual(c.floatingPosition, { x: 200, y: 500 });
    c.dragTo({ pointerId: 1, clientX: -100, clientY: -100, preventDefault() {} });
    c.finishDrag({ pointerId: 1 });
    assert.deepEqual(c.floatingPosition, { x: 12, y: 12 });
    assert.deepEqual(JSON.parse(saved.get(c.positionKey)), { x: 12, y: 12 });
    await c.toggle(); assert.equal(c.open, false);
});
test('keyboard movement, cancel and reset keep the assistant recoverable', () => {
    const { c, target } = agent(); c.nudgePosition(999, 999);
    assert.deepEqual(c.floatingPosition, { x: 292, y: 572 });
    c.beginDrag({ pointerId: 2, button: 0, clientX: 300, clientY: 580, currentTarget: target });
    c.dragTo({ pointerId: 2, clientX: 20, clientY: 20, preventDefault() {} }); c.cancelDrag({ pointerId: 2 });
    assert.deepEqual(c.floatingPosition, { x: 292, y: 572 });
    c.nudgePosition(-24, -24); c.resetPosition(); assert.deepEqual(c.floatingPosition, { x: 292, y: 572 });
});

test('full-page agent detaches and returns without losing conversation or unsaved project fields', () => {
    const { c } = agent(); c.embedded = true;
    const message = { text: 'Borrador', action: { draft: { name: 'TechStore', end: '2026-12-01' } } };
    c.messages = [message]; c.projectDraftMessage = message; c.question = 'Consulta pendiente';
    c.toggleFloating();
    assert.equal(c.isFloating, true); assert.equal(c.open, true); assert.equal(c.historyOpen, false);
    assert.equal(c.floatingPanelStyle.position, 'fixed'); assert.equal(c.launcherStyle.position, 'fixed');
    c.nudgePosition(-24, 0); c.toggleFloating();
    assert.equal(c.isFloating, false); assert.deepEqual(c.floatingPanelStyle, {});
    assert.equal(c.messages[0], message); assert.equal(c.projectDraftMessage.action.draft.end, '2026-12-01');
    assert.equal(c.question, 'Consulta pendiente');
});

test('saved floating position restores within bounds and is clamped on viewport resize', () => {
    const { c, saved, listeners } = agent();
    saved.set(c.positionKey, JSON.stringify({ x: 1800, y: 1200 }));
    c.initializeFloatingPosition(); assert.deepEqual(c.floatingPosition, { x: 292, y: 572 });
    window.innerWidth = 320; window.innerHeight = 300; listeners.resize();
    assert.deepEqual(c.floatingPosition, { x: 252, y: 232 });
});
