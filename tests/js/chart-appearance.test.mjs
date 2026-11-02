import test from 'node:test';
import assert from 'node:assert/strict';
import { chartAppearance, applyChartAppearance } from '../../resources/js/modules/chart-appearance.js';

test('chart theme updates labels and grids without changing values and restores the original colors', () => {
    const values = [4, 9], dataset = { data: values, backgroundColor: '#5d2a1a', borderColor: '#5d2a1a' };
    const chart = { config: { type: 'bar' }, options: { plugins: { legend: { labels: {} } }, scales: { x: {}, y: {} } }, data: { datasets: [dataset] }, update(mode) { assert.equal(mode, 'none'); } };
    const originals = new WeakMap();
    applyChartAppearance(chart, chartAppearance('dark'), originals);
    assert.equal(chart.options.plugins.legend.labels.color, '#bdc5d5');
    assert.equal(chart.options.scales.x.ticks.color, '#bdc5d5');
    assert.equal(dataset.backgroundColor, '#f0bfa1'); assert.equal(dataset.data, values);
    applyChartAppearance(chart, chartAppearance('light'), originals);
    assert.equal(chart.options.scales.y.ticks.color, '#656975');
    assert.equal(dataset.backgroundColor, '#5d2a1a'); assert.deepEqual(values, [4, 9]);
});

test('doughnut palette and separators follow the same theme as its labels', () => {
    const chart = { config: { type: 'doughnut' }, options: {}, data: { datasets: [{ data: [1, 2] }] }, update() {} };
    const originals = new WeakMap(), colors = chartAppearance('dark');
    applyChartAppearance(chart, colors, originals);
    assert.equal(chart.data.datasets[0].borderColor, colors.surface);
    assert.deepEqual(chart.data.datasets[0].backgroundColor, colors.palette);
    assert.equal(chart.options.color, colors.text);
});
