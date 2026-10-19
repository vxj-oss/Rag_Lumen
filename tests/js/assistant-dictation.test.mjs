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
