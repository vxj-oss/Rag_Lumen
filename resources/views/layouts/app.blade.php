<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gradient-to-br from-slate-50 via-white to-emerald-50/50 min-h-screen">
        <div class="lg:flex min-h-screen">
            <x-layouts.sidebar />

            <div class="flex-1 flex flex-col min-w-0">
                @isset($header)
                    <header class="relative bg-gradient-to-r from-white via-emerald-50/30 to-white border-b border-gray-200 overflow-hidden">
                        <div aria-hidden="true" class="pointer-events-none absolute -top-16 -right-16 w-56 h-56 rounded-full bg-emerald-300/20 blur-3xl"></div>
                        <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 left-1/3 w-48 h-48 rounded-full bg-teal-300/10 blur-3xl"></div>
                        <div class="relative max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1 min-w-0">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
