<script>
    (() => {
        let mode = 'system';
        try { const saved = localStorage.getItem('workspace.theme'); if (['light','dark','system'].includes(saved)) mode = saved; } catch (_) {}
        const theme = mode === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : mode;
        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
    })();
</script>
