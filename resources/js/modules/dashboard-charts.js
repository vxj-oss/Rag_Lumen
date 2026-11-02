import { Chart } from 'chart.js/auto';
import { chartAppearance, applyChartAppearance } from './chart-appearance.js';

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.color = '#656975';
Chart.defaults.borderColor = 'rgba(23, 25, 28, 0.07)';
Chart.defaults.plugins.tooltip.backgroundColor = '#1b1d1f';
Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
Chart.defaults.plugins.tooltip.bodyColor = '#d9e7f5';
Chart.defaults.datasets.doughnut.borderColor = '#ffffff';
Chart.defaults.datasets.doughnut.borderWidth = 3;
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) Chart.defaults.animation = false;

const originalStyles = new WeakMap();
function syncChartAppearance() {
    const colors = chartAppearance(document.documentElement.dataset.theme);
    Chart.defaults.color = colors.text;
    Chart.defaults.borderColor = colors.grid;
    Chart.defaults.datasets.doughnut.borderColor = colors.surface;
    for (const chart of Object.values(Chart.instances)) applyChartAppearance(chart, colors, originalStyles);
}
syncChartAppearance();
const themeObserver = new MutationObserver(syncChartAppearance);
themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
window.addEventListener('pagehide', () => themeObserver.disconnect(), { once: true });

function toChartData(labelValueMap) {
    if (!labelValueMap || typeof labelValueMap !== 'object') {
        return { labels: [], values: [] };
    }

    const compact = window.innerWidth < 640;
    const labels = Object.keys(labelValueMap).map((label) => (
        compact && label.length > 18 ? `${label.slice(0, 17)}…` : label
    ));
    const values = Object.values(labelValueMap);

    return { labels, values };
}

function renderDonut(canvasId, labelValueMap) {
    const canvas = document.getElementById(canvasId);

    if (!canvas) {
        return;
    }

    const { labels, values } = toChartData(labelValueMap);

    if (labels.length === 0) {
        return;
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: chartAppearance(document.documentElement.dataset.theme).palette }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 12 } },
                },
            },
        },
    });
}

function renderBar(canvasId, labelValueMap, color = '#5d2a1a', horizontal = false) {
    const canvas = document.getElementById(canvasId);

    if (!canvas) {
        return;
    }

    const { labels, values } = toChartData(labelValueMap);

    if (labels.length === 0) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: color, borderRadius: 4 }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                [horizontal ? 'x' : 'y']: {
                    beginAtZero: true,
                },
            },
        },
    });
}

function renderLine(canvasId, labels = [], values = []) {
    const canvas = document.getElementById(canvasId);

    if (!canvas || !labels || labels.length === 0) {
        return;
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: values,
                borderColor: '#5d2a1a',
                backgroundColor: 'rgba(93, 42, 26, 0.06)',
                fill: true,
                tension: 0.3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
}

export function initDashboard() {
    const data = window.dashboardData;

    if (!data) {
        return;
    }

    renderDonut('chart-projects-by-status', data.projectsByStatus);
    renderDonut('chart-tasks-by-status', data.tasksByStatus);
    renderBar('chart-risk-by-project', data.riskByProject, '#a45e50', true);
    renderBar('chart-progress-by-project', data.progressByProject, '#5d2a1a', true);
    renderBar('chart-employee-performance', data.employeePerformance, '#737782');

    if (data.progressEvolution) {
        renderLine(
            'chart-progress-evolution',
            data.progressEvolution.labels || [],
            data.progressEvolution.values || []
        );
    }
    syncChartAppearance();
}

