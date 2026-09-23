@php
    $navItems = [];

    if (auth()->user()->can('viewAny', \App\Models\Project::class)) {
        $navItems[] = ['label' => 'Proyectos', 'route' => 'projects.index', 'pattern' => 'projects.*', 'icon' => 'folder'];
    }
    if (auth()->user()->can('viewAny', \App\Models\Task::class)) {
        $navItems[] = ['label' => 'Tareas', 'route' => 'tasks.index', 'pattern' => 'tasks.*', 'icon' => 'check'];
    }
    if (auth()->user()->can('viewAny', \App\Models\Employee::class)) {
        $navItems[] = ['label' => 'Empleados', 'route' => 'employees.index', 'pattern' => 'employees.*', 'icon' => 'users'];
    }
    if (auth()->user()->can('viewAny', \App\Models\Client::class)) {
        $navItems[] = ['label' => 'Clientes', 'route' => 'clients.index', 'pattern' => 'clients.*', 'icon' => 'briefcase'];
    }
    if (auth()->user()->can('viewAny', \App\Models\Area::class)) {
        $navItems[] = ['label' => 'Áreas', 'route' => 'areas.index', 'pattern' => 'areas.*', 'icon' => 'squares'];
    }
    if (auth()->user()->can('viewAny', \App\Models\RagDocument::class)) {
        $navItems[] = ['label' => 'Documentos', 'route' => 'rag-documents.index', 'pattern' => 'rag-documents.*', 'icon' => 'document'];
        $navItems[] = ['label' => 'Asistente', 'route' => 'rag-query.index', 'pattern' => 'rag-query.*', 'icon' => 'chat'];
    }
    if (auth()->user()->hasRole([\App\Support\Enums\RoleName::Administrator->value, \App\Support\Enums\RoleName::Manager->value]) || auth()->user()->isLeader()) {
        $navItems[] = ['label' => 'Actividad', 'route' => 'activity.index', 'pattern' => 'activity.*', 'icon' => 'activity'];
    }

    $unreadAlerts = auth()->user()->unreadNotifications()->count();

    $icons = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.125 1.125 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />',
        'folder' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5M4.5 9.75V6a2.25 2.25 0 012.25-2.25h4.94a2.25 2.25 0 011.591.659l1.06 1.06a.75.75 0 00.531.22H19.5A2.25 2.25 0 0121.75 8v1.75" />',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
        'briefcase' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />',
        'squares' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />',
        'activity' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ];
@endphp

<div x-data="{
        mobileOpen: false,
        collapsed: localStorage.getItem('sidebar-collapsed') === '1',
    }"
    x-init="$watch('mobileOpen', (value) => document.body.classList.toggle('overflow-y-hidden', value))">
    <div class="lg:hidden flex items-center justify-between bg-slate-900 text-white px-4 py-3">
        <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight min-h-[44px] inline-flex items-center">{{ config('app.name') }}</a>
        <button @click="mobileOpen = true" class="p-2 -mr-2 min-w-[44px] min-h-[44px] inline-flex items-center justify-center" aria-label="{{ __('Abrir menú') }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="mobileOpen = false"></div>

    <aside
        x-show="true"
        :class="[
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            collapsed ? 'lg:!w-20' : '',
        ]"
        class="fixed inset-y-0 left-0 z-50 w-64 h-screen bg-slate-900 text-slate-200 flex flex-col transition-[width,transform] duration-200 lg:translate-x-0 lg:static lg:sticky lg:top-0 lg:z-auto"
    >
        <div class="h-16 flex items-center gap-2 px-5 border-b border-slate-800 shrink-0" :class="collapsed ? 'lg:px-0 lg:justify-center' : ''">
            <button @click="collapsed = !collapsed; localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0')"
                :aria-label="collapsed ? @js(__('Expandir menú')) : @js(__('Colapsar menú'))"
                class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center font-bold text-white text-sm shadow-sm transition-transform duration-300 ease-out hover:rotate-6 hover:scale-110 shrink-0 lg:cursor-pointer">
                {{ Str::of(config('app.name'))->substr(0, 1) }}
            </button>
            <span x-show="!collapsed" class="font-semibold text-white tracking-tight truncate">{{ config('app.name') }}</span>
            <button @click="collapsed = !collapsed; localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0')"
                x-show="!collapsed"
                :aria-label="collapsed ? @js(__('Expandir menú')) : @js(__('Colapsar menú'))"
                class="hidden lg:inline-flex ml-auto p-2 -mr-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition min-w-[44px] min-h-[44px] items-center justify-center shrink-0">
                <svg class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}" title="{{ __('Dashboard') }}"
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200
                      {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-900/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icons['home'] !!}</svg>
                <span x-show="!collapsed" class="truncate">{{ __('Dashboard') }}</span>
            </a>

            @foreach ($navItems as $item)
                <a href="{{ route($item['route']) }}" title="{{ __($item['label']) }}"
                   class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200
                          {{ request()->routeIs($item['pattern']) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-900/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icons[$item['icon']] !!}</svg>
                    <span x-show="!collapsed" class="truncate">{{ __($item['label']) }}</span>
                </a>
            @endforeach

            <a href="{{ route('alerts.index') }}" title="{{ __('Alertas') }}"
               x-data="{ count: {{ $unreadAlerts }} }"
               x-init="setInterval(() => {
                   fetch(@js(route('alerts.unread-count')), { headers: { 'Accept': 'application/json' } })
                       .then(res => res.ok ? res.json() : null)
                       .then(data => { if (data) count = data.count; })
                       .catch(() => {});
               }, 20000)"
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200
                      {{ request()->routeIs('alerts.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-900/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icons['bell'] !!}</svg>
                <span x-show="!collapsed" class="flex-1 truncate">{{ __('Alertas') }}</span>
                <span x-show="count > 0" x-cloak x-text="count > 99 ? '99+' : count"
                      class="bg-red-500 text-white text-xs font-semibold rounded-full px-2 py-0.5 min-w-[1.25rem] text-center animate-pulse"></span>
            </a>
        </nav>

        <div class="border-t border-slate-800 p-3 shrink-0">
            <x-ui.dropdown align="top" width="56">
                <x-slot name="trigger">
                    <button class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition-colors duration-200 text-left min-h-[44px]" aria-label="{{ __('Cuenta de usuario') }}">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-sm font-semibold text-white shrink-0 ring-2 ring-transparent hover:ring-emerald-400/50 transition">
                            {{ Str::of(Auth::user()->name)->substr(0, 1) }}
                        </div>
                        <div x-show="!collapsed" class="min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-ui.dropdown-link :href="route('profile.edit')">{{ __('Perfil') }}</x-ui.dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Cerrar sesión') }}
                        </x-ui.dropdown-link>
                    </form>
                </x-slot>
            </x-ui.dropdown>
        </div>
    </aside>
</div>
