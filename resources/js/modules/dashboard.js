let loading;
function loadCharts() {
    loading ||= import('./dashboard-charts.js').then(module => module.initDashboard());
    return loading;
}
function watchCharts() {
    const canvases = [...document.querySelectorAll('canvas[id^="chart-"]')];
    if (!canvases.length) return;
    if (!('IntersectionObserver' in window)) { loadCharts(); return; }
    const observer = new IntersectionObserver(entries => {
        if (!entries.some(entry => entry.isIntersecting)) return;
        observer.disconnect(); loadCharts();
    }, { rootMargin: '200px' });
    canvases.forEach(canvas => observer.observe(canvas));
    window.addEventListener('pagehide', () => observer.disconnect(), { once: true });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', watchCharts, { once: true });
else watchCharts();
