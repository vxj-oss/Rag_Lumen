@props(['title', 'subtitle' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} · {{ config('app.name', 'Laravel') }}</title>
    <x-ui.theme-init />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="workspace-theme auth-page font-sans antialiased">
    <main class="auth-scene" x-data="authScene" :class="{ 'is-paused': paused || reducedMotion }" @pointermove="move($event)" @pointerleave="reset()">
        <div class="auth-art" aria-hidden="true">
            <div class="auth-parallax">
                <div class="auth-paper"></div><div class="auth-paper auth-paper-two"></div>
                <div class="auth-orbit"></div><div class="auth-orbit auth-orbit-two"></div>
            </div>
        </div>
        <section class="auth-form-card" aria-labelledby="auth-title">
            <div class="auth-emblem" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M12 3 21 8v8l-9 5-9-5V8l9-5Z"/><path d="m3 8 9 5 9-5M12 13v8M7.5 5.5l9 5v8"/></svg>
            </div>
            <div class="mb-7">
                <h1 id="auth-title">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $slot }}
            <footer class="auth-footer">
                <x-ui.theme-switch />
                <a href="{{ route('legal.security-policy') }}" class="underline">{{ __('Privacidad y seguridad') }}</a>
                <button type="button" class="auth-motion-toggle" @click="toggleMotion()" :disabled="reducedMotion" :aria-pressed="(paused || reducedMotion).toString()" :aria-label="reducedMotion ? 'Movimiento reducido activado en tu dispositivo' : (paused ? 'Reanudar animación del fondo' : 'Pausar animación del fondo')">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path x-show="!paused && !reducedMotion" d="M5 3v10M11 3v10"/><path x-show="paused || reducedMotion" d="m5 3 7 5-7 5V3Z"/></svg>
                    <span x-text="reducedMotion ? 'Fondo estático' : (paused ? 'Animar fondo' : 'Pausar fondo')">Pausar fondo</span>
                </button>
            </footer>
        </section>
    </main>
    <x-ui.toasts />
</body>
</html>
