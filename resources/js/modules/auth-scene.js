export function registerAuthScene(Alpine) {
    Alpine.data('authScene', () => ({
        paused: false,
        reducedMotion: false,
        media: null,
        onMotionChange: null,
        init() {
            this.media = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.reducedMotion = this.media.matches;
            try { this.paused = localStorage.getItem('auth-background-paused') === '1'; } catch {}
            this.onMotionChange = event => { this.reducedMotion = event.matches; this.reset(); };
            this.media.addEventListener('change', this.onMotionChange);
        },
        move(event) {
            if (this.paused || this.reducedMotion || event.pointerType !== 'mouse') return;
            const x = (event.clientX / Math.max(1, window.innerWidth) - .5) * 16;
            const y = (event.clientY / Math.max(1, window.innerHeight) - .5) * 12;
            this.$el.style.setProperty('--pointer-x', `${x}px`);
            this.$el.style.setProperty('--pointer-y', `${y}px`);
        },
        reset() {
            this.$el.style.setProperty('--pointer-x', '0px');
            this.$el.style.setProperty('--pointer-y', '0px');
        },
        toggleMotion() {
            if (this.reducedMotion) return;
            this.paused = !this.paused;
            this.reset();
            try { localStorage.setItem('auth-background-paused', this.paused ? '1' : '0'); } catch {}
        },
        destroy() {
            this.media?.removeEventListener('change', this.onMotionChange);
        },
    }));
}
