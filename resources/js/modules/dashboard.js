import { Chart } from 'chart.js/auto';

Chart.defaults.font.family = '"Plus Jakarta Sans", sans-serif';
Chart.defaults.color = '#626d7b';
Chart.defaults.borderColor = 'rgba(62, 82, 105, 0.12)';
Chart.defaults.plugins.tooltip.backgroundColor = '#1b1d1f';
Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
Chart.defaults.plugins.tooltip.bodyColor = '#d9e7f5';
Chart.defaults.datasets.doughnut.borderColor = '#ffffff';
Chart.defaults.datasets.doughnut.borderWidth = 3;
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) Chart.defaults.animation = false;

const palette = ['#3c86be', '#68addd', '#d6a44d', '#cf747d', '#4d9c88', '#828cb7', '#7d9db8', '#52687b'];

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
            datasets: [{ data: values, backgroundColor: palette }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } },
                },
            },
        },
    });
}

function renderBar(canvasId, labelValueMap, color = '#059669', horizontal = false) {
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
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.1)',
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

function initDashboard() {
    const data = window.dashboardData;

    if (!data) {
        return;
    }

    renderDonut('chart-projects-by-status', data.projectsByStatus);
    renderDonut('chart-tasks-by-status', data.tasksByStatus);
    renderBar('chart-risk-by-project', data.riskByProject, '#dc2626', true);
    renderBar('chart-progress-by-project', data.progressByProject, '#059669', true);
    renderBar('chart-employee-performance', data.employeePerformance, '#2563eb');

    if (data.progressEvolution) {
        renderLine(
            'chart-progress-evolution',
            data.progressEvolution.labels || [],
            data.progressEvolution.values || []
        );
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}
