<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <script>try { if (localStorage.getItem('sidebar-collapsed') === '1') document.documentElement.classList.add('sidebar-collapsed'); } catch (e) {}</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-responsive-list="{{ request()->routeIs('projects.index','employees.index','clients.index','areas.index','rag-documents.index','users.index','alerts.index','activity.index','normativa.index','archive.index','employees.record','employees.show','workspaces.show') ? '1' : '0' }}" class="workspace-theme font-sans antialiased text-slate-800 {{ request()->routeIs('rag-query.index') ? 'assistant-page' : '' }}">
        <div class="app-shell lg:flex">
            <x-layouts.sidebar />

            <div class="app-content flex-1 flex flex-col min-w-0">
                <x-ui.workspace-toolbar />
                @isset($header)
                    <header class="workspace-page-header relative bg-white/70 backdrop-blur-md border-b border-slate-200/70 overflow-hidden">
                        <div class="relative max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="app-main flex-1 min-w-0">
                    {{ $slot }}
                </main>
            </div>
        </div>
        <x-ui.toasts />
        <x-assistant.widget />
    </body>
</html>
