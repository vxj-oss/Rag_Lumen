<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <x-ui.theme-init />

        <script>try { if (localStorage.getItem('sidebar-collapsed') === '1') document.documentElement.classList.add('sidebar-collapsed'); } catch (e) {}</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-list-size="{{ \App\Support\ListPagination::size() }}" data-responsive-list="{{ request()->routeIs('projects.index','projects.show','employees.index','clients.index','areas.index','rag-documents.index','rag-documents.source','users.index','alerts.index','activity.index','normativa.index','archive.index','employees.record','employees.show','workspaces.show') ? '1' : '0' }}" class="workspace-theme font-sans antialiased text-slate-800 {{ request()->routeIs('rag-query.index') ? 'assistant-page' : '' }}">
        <div class="app-shell lg:flex">
            <x-layouts.sidebar />

            <div class="app-content flex-1 flex flex-col min-w-0">
                <x-ui.workspace-toolbar />
                @isset($header)
                    {{ $header }}
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
