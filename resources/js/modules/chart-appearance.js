export function chartAppearance(mode) {
    const dark = mode === 'dark';
    return {
        dark, text: dark ? '#bdc5d5' : '#656975', surface: dark ? '#1b1c21' : '#ffffff',
        grid: dark ? 'rgba(189,197,213,.12)' : 'rgba(23,25,28,.07)',
        palette: dark
            ? ['#f0bfa1', '#cbb6a6', '#b5bfce', '#e8bda5', '#a5bcc2', '#d1d6e1', '#b9a89d', '#8f9cae']
            : ['#5d2a1a', '#bba597', '#737782', '#d9bca8', '#969a9f', '#444851', '#c5b7ad', '#d6d6d9'],
    };
}

function recolor(value, dark) {
    if (!dark) return value;
    if (Array.isArray(value)) return value.map(item => recolor(item, dark));
    return ({ '#5d2a1a': '#f0bfa1', '#a45e50': '#f0a69c', '#737782': '#b5bfce', 'rgba(93, 42, 26, 0.06)': 'rgba(240,191,161,.12)' })[value] ?? value;
}

export function applyChartAppearance(chart, colors, originalStyles) {
    chart.options.color = colors.text;
    const legend = chart.options.plugins?.legend;
    if (legend && typeof legend === 'object') { legend.labels ||= {}; legend.labels.color = colors.text; }
    for (const axis of Object.values(chart.options.scales || {})) {
        axis.ticks ||= {}; axis.ticks.color = colors.text;
        axis.grid ||= {}; axis.grid.color = colors.grid;
    }
    for (const dataset of chart.data.datasets) {
        if (!originalStyles.has(dataset)) originalStyles.set(dataset, { background: dataset.backgroundColor, border: dataset.borderColor });
        const original = originalStyles.get(dataset);
        dataset.backgroundColor = chart.config.type === 'doughnut' ? colors.palette : recolor(original.background, colors.dark);
        dataset.borderColor = chart.config.type === 'doughnut' ? colors.surface : recolor(original.border, colors.dark);
    }
    chart.update('none');
}
