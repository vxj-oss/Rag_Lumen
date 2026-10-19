export function registerWorkspace(Alpine) {
    Alpine.data('workspaceSearch', (endpoint) => ({
        query: '', results: [], loading: false, error: '', controller: null, sequence: 0,
        async search() {
            const sequence = ++this.sequence;
            this.controller?.abort();
            this.results = [];
            this.error = '';
            if (this.query.trim().length < 2) { this.loading = false; return; }
            this.controller = new AbortController();
            this.loading = true;
            try {
                const response = await fetch(`${endpoint}?q=${encodeURIComponent(this.query.trim())}`, {
                    headers: { Accept: 'application/json' }, signal: this.controller.signal,
                });
                if (!response.ok) throw new Error();
                const data = await response.json();
                if (sequence === this.sequence) this.results = data.results;
            } catch (error) {
                if (sequence === this.sequence && error.name !== 'AbortError') this.error = 'No se pudo buscar. Intenta de nuevo.';
            } finally { if (sequence === this.sequence) this.loading = false; }
        },
        destroy() { this.controller?.abort(); },
    }));

    Alpine.data('projectWorkspace', () => ({
        tab: 'overview',
        init() {
            const requested = new URLSearchParams(location.search).get('tab');
            if (['overview', 'tasks', 'team', 'documents', 'history'].includes(requested)) this.tab = requested;
        },
        select(tab) {
            this.tab = tab;
            const url = new URL(location.href);
            url.searchParams.set('tab', tab);
            history.replaceState(null, '', url);
        },
    }));

    // Delegation also covers file inputs and forms rendered inside Alpine templates.
    document.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file') return;
        input.parentElement.querySelector('[data-file-preview]')?.remove();
        if (!input.files?.length) return;
        const preview = document.createElement('div');
        preview.dataset.filePreview = '';
        preview.className = 'file-preview';
        const file = input.files[0];
        const description = document.createElement('p');
        description.textContent = `${file.name} · ${(file.size / 1024).toFixed(0)} KB${input.files.length > 1 ? ` · ${input.files.length} archivos` : ''}`;
        preview.append(description);
        // Preview only raster formats; other documents keep a safe filename summary.
        if (['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(file.type)) {
            const image = document.createElement('img');
            const url = URL.createObjectURL(file);
            image.alt = 'Vista previa del archivo seleccionado';
            image.className = 'mt-3 max-h-36 rounded-lg object-contain';
            image.onload = image.onerror = () => URL.revokeObjectURL(url);
            image.src = url;
            preview.append(image);
        }
        input.insertAdjacentElement('afterend', preview);
    });
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (event.defaultPrevented || !form.matches('[data-submit-feedback]')) return;
        if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        // Keep the submitter enabled so its name/value is included in the request.
        const button = event.submitter;
        if (button) { button.dataset.originalLabel = button.innerHTML; button.textContent = 'Guardando…'; button.setAttribute('aria-disabled', 'true'); }
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-submit-feedback]').forEach((form) => {
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('[data-original-label]').forEach((button) => {
                button.innerHTML = button.dataset.originalLabel;
                button.removeAttribute('aria-disabled');
                delete button.dataset.originalLabel;
            });
        });
    });
}
