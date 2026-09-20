<div x-data="{
        toasts: [],
        push(message, type) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.dismiss(id), 4000);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
        init() {
            window.toast = (message, type = 'success') => this.push(message, type);
        },
    }"
    class="fixed bottom-4 right-4 z-[60] w-[calc(100vw-2rem)] max-w-sm space-y-2"
    aria-live="polite">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :role="toast.type === 'error' ? 'alert' : 'status'"
            class="flex items-start gap-3 rounded-xl px-4 py-3 shadow-lg ring-1"
            :class="toast.type === 'error'
                ? 'bg-red-600 text-white ring-red-700'
                : 'bg-emerald-600 text-white ring-emerald-700'">
            <svg x-show="toast.type !== 'error'" class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg x-show="toast.type === 'error'" x-cloak class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.374c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="flex-1 min-w-0 text-sm font-medium break-words" x-text="toast.message"></p>
            <button @click="dismiss(toast.id)" :aria-label="@js(__('Cerrar aviso'))"
                class="shrink-0 -mr-1 p-1.5 min-w-[44px] min-h-[44px] -m-2 inline-flex items-center justify-center rounded-lg hover:bg-white/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>

@if (session('status') || session('toast_error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const show = () => {
                if (typeof window.toast !== 'function') {
                    setTimeout(show, 50);
                    return;
                }
                @if (session('toast_error'))
                    window.toast(@js(session('toast_error')), 'error');
                @endif
                @if (session('status'))
                    window.toast(@js(session('status')), 'success');
                @endif
            };
            show();
        });
    </script>
@endif
