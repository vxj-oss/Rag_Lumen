<button type="button" class="workspace-theme-switch" x-data @click="$store.appearance.toggle()" :aria-pressed="($store.appearance.resolved === 'dark').toString()" :aria-label="$store.appearance.resolved === 'dark' ? 'Activar tema claro' : 'Activar tema oscuro'" :title="$store.appearance.resolved === 'dark' ? 'Activar tema claro' : 'Activar tema oscuro'">
    <svg x-show="$store.appearance.resolved === 'dark'" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg>
    <svg x-show="$store.appearance.resolved === 'light'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20.5 14.5A9 9 0 019.5 3.5a9 9 0 1011 11Z"/></svg>
    <span class="sr-only">Tema de la interfaz</span>
</button>
