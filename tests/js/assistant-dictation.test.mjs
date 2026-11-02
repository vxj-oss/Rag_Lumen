import test from 'node:test';
import assert from 'node:assert/strict';
import { registerAssistant } from '../../resources/js/modules/assistant.js';

let factory;
registerAssistant({ data: (_, create) => { factory = create; } });
globalThis.window = {};
function client() {
    const instance = factory({ userId: 1, voiceAvailable: true });
    instance.$nextTick = callback => callback();
    instance.$refs = { question: { focus() {} } };
    instance.open = true;
    return instance;
}
class Recognition {
    start() { this.onstart(); }
    stop() { this.onend(); }
    abort() { this.onend(); }
}
function result(text, final = false) {
    const value = [{ transcript: text }];
    value.isFinal = final;
    return value;
}

test('live dictation shows interim words, replaces them and preserves the existing draft', () => {
    const c = client(); c.question = 'Consulta:';
    c.startLiveDictation(Recognition);
    assert.equal(c.recording, true);
    c.recognition.onresult({ resultIndex: 0, results: [result('cuántos')] });
    assert.equal(c.question, 'Consulta: cuántos');
    c.recognition.onresult({ resultIndex: 0, results: [result('cuántos empleados', true), result('activos')] });
    assert.equal(c.question, 'Consulta: cuántos empleados activos');
    c.recognition.onresult({ resultIndex: 1, results: [result('cuántos empleados', true), result('activos hay', true)] });
    assert.equal(c.question, 'Consulta: cuántos empleados activos hay');
    c.finishRecording();
    assert.equal(c.recording, false);
    assert.match(c.dictationHint, /Revisa/);
});

test('microphone permission failure is visible and closing releases recognition', () => {
    const c = client(); c.startLiveDictation(Recognition);
    c.recognition.onerror({ error: 'not-allowed' });
    assert.match(c.error, /permisos/);
    c.stopAll();
    assert.equal(c.recording, false);
    assert.equal(c.recognition, null);
});

test('recorded transcription appends text instead of deleting the draft', async () => {
    const c = client(); c.dictationBase = 'Consulta:';
    c.headers = () => ({});
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ text: 'Mis actividades' }) });
    await c.transcribe(new Blob(['a'.repeat(120)], { type: 'audio/webm' }));
    assert.equal(c.question, 'Consulta: Mis actividades');
    assert.equal(c.transcribing, false);
});

test('reviewed activity submits once and exposes the confirmed record link', async () => {
    globalThis.location = { origin: 'http://localhost' };
    const c = client(); c.headers = () => ({});
    const message = { action: { url: '/tasks', projects: [{ id: 1, state_id: 2 }], draft: { project_id: 1, assignee_id: 3, title: 'Revisión', priority: 'medium', tasks: [{ title: 'Validar' }] } } };
    let requests = 0;
    globalThis.fetch = async (_, options) => {
        requests++;
        assert.equal(JSON.parse(options.body).tareas[0].titulo, 'Validar');
        return { ok: true, json: async () => ({ task: { id: 42, url: 'http://localhost/tasks/42' } }) };
    };
    await c.createActivity(message);
    await c.createActivity(message);
    assert.equal(requests, 1);
    assert.equal(message.action.createdUrl, 'http://localhost/tasks/42');
});

test('activity validation failures preserve the editable draft', async () => {
    globalThis.location = { origin: 'http://localhost' };
    const c = client(); c.headers = () => ({});
    const message = { action: { url: '/tasks', projects: [{ id: 1, state_id: 2 }], draft: { project_id: 1, assignee_id: 3, title: 'Revisión', tasks: [{ title: 'Validar' }] } } };
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ errors: { asignado_a: ['El responsable dejó el proyecto.'] } }) });
    await c.createActivity(message);
    assert.match(message.action.error, /responsable/);
    assert.equal(message.action.draft.title, 'Revisión');
    assert.equal(message.action.submitting, false);
    assert.equal(message.action.created, undefined);
});

test('project creation sends reviewed fields and keeps a single confirmed action', async () => {
    globalThis.location = { origin: 'http://localhost' };
    const c = client(); c.headers = () => ({});
    const message = { action: { url: '/projects', draft: { code: 'PR-01', name: 'Campaña de marketing', type: 'digital_marketing', area_ids: ['3'], start: '2026-10-18', end: '2026-11-18', priority: 'medium' } } };
    let calls = 0;
    globalThis.fetch = async (_, options) => {
        calls++;
        const data = JSON.parse(options.body);
        assert.equal(data.nombre, 'Campaña de marketing');
        assert.equal(data.estado, 'planning');
        assert.deepEqual(data.area_ids, ['3']);
        return { ok: true, json: async () => ({ project: { id: 5, title: data.nombre, url: 'http://localhost/projects/5' } }) };
    };
    await c.createProject(message); await c.createProject(message);
    assert.equal(calls, 1); assert.equal(message.action.created, true);
    assert.equal(message.action.createdUrl, 'http://localhost/projects/5');
});

test('project editor opens without saving and reopens the same editable draft', () => {
    const c = client(), events = [];
    c.$dispatch = (...event) => events.push(event);
    const message = { action: { type: 'create_project', draft: { name: 'TechStore', code: 'PR-01', end: '' } } };
    c.editProject(message);
    assert.equal(c.projectDraftMessage, message);
    c.projectDraftMessage.action.draft.end = '2026-12-01';
    c.projectDraftMessage.action.draft.code = 'TECH-01';
    c.editProject(message);
    assert.equal(c.projectDraftMessage.action.draft.code, 'TECH-01');
    assert.equal(c.projectDraftMessage.action.draft.end, '2026-12-01');
    assert.deepEqual(events[0], ['open-modal', 'floating-project-draft']);
    assert.equal(message.action.created, undefined);
});

test('copying a response writes the answer and marks only that response', async () => {
    const original = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
    const c = client(); let copied;
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { clipboard: { writeText: async text => { copied = text; } } } });
    try {
        await c.copyAnswer('Resumen de proyectos', 3);
        assert.equal(copied, 'Resumen de proyectos');
        assert.equal(c.copiedMessage, 3);
    } finally {
        if (original) Object.defineProperty(globalThis, 'navigator', original); else delete globalThis.navigator;
    }
});

test('clipboard denial leaves the answer available and explains how to copy it', async () => {
    const original = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
    const c = client();
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { clipboard: { writeText: async () => { throw new Error('denied'); } } } });
    try {
        await c.copyAnswer('Respuesta conservada', 1);
        assert.equal(c.copiedMessage, null);
        assert.match(c.error, /seleccionar el texto/);
    } finally {
        if (original) Object.defineProperty(globalThis, 'navigator', original); else delete globalThis.navigator;
    }
});

test('project validation preserves all fields and permits correction before successful creation', async () => {
    globalThis.location = { origin: 'http://localhost' };
    const c = client(), events = [];
    c.headers = () => ({}); c.$dispatch = (...event) => events.push(event);
    const message = { action: { type: 'create_project', url: '/projects', draft: { name: 'TechStore', code: 'TECH-01', type: 'other', start: '2026-10-19', end: '2026-10-18', area_ids: ['2'], responsible_id: 3, client_id: 4, budget: 0 } } };
    c.editProject(message);
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ errors: { fecha_fin_estimada: ['La entrega debe ser posterior al inicio.'] } }) });
    await c.createProject(message);
    assert.match(message.action.error, /entrega/);
    assert.equal(message.action.submitting, false);
    assert.deepEqual(message.action.draft.area_ids, ['2']);
    assert.equal(message.action.draft.responsible_id, 3);
    assert.equal(events.length, 1);
    message.action.draft.end = '2026-12-01';
    globalThis.fetch = async (_,options) => {
        const data = JSON.parse(options.body);
        assert.equal(data.fecha_fin_estimada, '2026-12-01');
        assert.equal(data.cliente_id, 4);
        assert.equal(data.presupuesto, 0);
        return { ok: true, json: async () => ({ project: { title: 'TechStore', url: '/projects/6' } }) };
    };
    await c.createProject(message);
    assert.equal(message.action.created, true);
    assert.deepEqual(events[1], ['close-modal', 'floating-project-draft']);
});
