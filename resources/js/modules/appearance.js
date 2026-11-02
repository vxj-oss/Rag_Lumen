const MODES = ['light', 'dark', 'system'];

export function createAppearance({ root, storage, media, events }) {
    return {
        mode: 'system', resolved: 'light', mediaListener: null, storageListener: null,
        init() {
            try { const saved = storage.getItem('workspace.theme'); if (MODES.includes(saved)) this.mode = saved; } catch {}
            this.apply();
            this.mediaListener = () => { if (this.mode === 'system') this.apply(); };
            this.storageListener = event => {
                if (event.key !== 'workspace.theme') return;
                this.mode = MODES.includes(event.newValue) ? event.newValue : 'system'; this.apply();
            };
            media.addEventListener('change', this.mediaListener);
            events.addEventListener('storage', this.storageListener);
        },
        apply() {
            this.resolved = this.mode === 'system' ? (media.matches ? 'dark' : 'light') : this.mode;
            root.dataset.theme = this.resolved; root.style.colorScheme = this.resolved;
        },
        setMode(mode) {
            if (!MODES.includes(mode)) return;
            this.mode = mode; this.apply();
            try { storage.setItem('workspace.theme', mode); } catch {}
        },
        toggle() { this.setMode(this.resolved === 'dark' ? 'light' : 'dark'); },
        destroy() {
            media.removeEventListener('change', this.mediaListener);
            events.removeEventListener('storage', this.storageListener);
        },
    };
}

export function registerAppearance(Alpine) {
    let storage;
    try { storage = window.localStorage; } catch { storage = { getItem() {}, setItem() {} }; }
    Alpine.store('appearance', createAppearance({ root: document.documentElement, storage, media: window.matchMedia('(prefers-color-scheme: dark)'), events: window }));
}
