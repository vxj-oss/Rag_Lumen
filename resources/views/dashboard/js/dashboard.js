import { Chart } from 'chart.js/auto';

const palette = ['#059669', '#2563eb', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#65a30d', '#db2777'];

function toChartData(labelValueMap) {
    if (!labelValueMap || typeof labelValueMap !== 'object') {
        return { labels: [], values: [] };
    }

    const labels = Object.keys(labelValueMap);
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