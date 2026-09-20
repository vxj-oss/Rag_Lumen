@props(['title', 'subtitle' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} · {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-dvh flex">
            <div class="hidden lg:flex lg:w-[45%] xl:w-1/2 flex-col justify-between bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 text-white p-10 xl:p-14" aria-hidden="true">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center font-bold text-white text-lg shadow-lg">
                        {{ Str::of(config('app.name'))->substr(0, 1) }}
                    </div>
                    <span class="font-semibold text-xl tracking-tight">{{ config('app.name') }}</span>
                </div>

                <div class="space-y-8">
                    <div class="space-y-3">
                        <h1 class="text-3xl xl:text-4xl font-extrabold tracking-tight leading-tight">
                            {{ __('Gestión de proyectos con inteligencia artificial') }}
                        </h1>
                        <p class="text-slate-300 text-base xl:text-lg leading-relaxed">
                            {{ __('Proyectos, tareas y equipo bajo control, con un asistente que responde citando tus documentos.') }}
                        </p>
                    </div>

                    <ul class="space-y-4 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <span><strong class="font-semibold">{{ __('Tablero operativo.') }}</strong> {{ __('Tareas, avances y bloqueos en tiempo real.') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            </span>
                            <span><strong class="font-semibold">{{ __('Riesgo bajo control.') }}</strong> {{ __('Alertas automáticas antes de que algo se atrase.') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                            </span>
                            <span><strong class="font-semibold">{{ __('Asistente RAG.') }}</strong> {{ __('Pregunta sobre tus proyectos con fuentes citadas.') }}</span>
                        </li>
                    </ul>
                </div>

                <p class="text-xs text-slate-400">{{ __('Uso interno de la empresa.') }}</p>
            </div>

            <div class="flex-1 flex flex-col items-center justify-center px-4 py-10 sm:px-6 bg-gradient-to-br from-slate-50 via-white to-emerald-50/50">
                <div class="lg:hidden flex items-center gap-2 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center font-bold text-white shadow-lg">
                        {{ Str::of(config('app.name'))->substr(0, 1) }}
                    </div>
                    <span class="font-semibold text-gray-900 text-lg tracking-tight">{{ config('app.name') }}</span>
                </div>

                <div class="w-full max-w-md bg-white shadow-xl ring-1 ring-gray-900/5 rounded-2xl px-6 py-8 sm:px-8">
                    <h2 class="text-xl font-bold text-gray-900 tracking-tight">{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
                    @endif

                    <div class="mt-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        <x-ui.toasts />
    </body>
</html>
