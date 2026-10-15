@php
    $navItems = [];

    if (auth()->user()->can('viewAny', \App\Models\Project::class)) {
        $navItems[] = ['label' => 'Proyectos', 'route' => 'projects.index', 'pattern' => 'projects.*', 'icon' => 'folder'];
    }
    if (auth()->user()->can('viewAny', \App\Models\Task::class)) {
        $navItems[] = ['label' => 'Actividades', 'route' => 'tasks.index', 'pattern' => 'tasks.*', 'icon' => 'check'];
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
    $navItems[] = ['label' => 'Normativa', 'route' => 'normativa.index', 'pattern' => 'normativa.*', 'icon' => 'book'];
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

@php
    $icons['book'] = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />';

    $porRuta = collect($navItems)->keyBy('route');

    // Apartados del menú: cada uno se pliega y recuerda su estado.
    $apartados = [
        'trabajo' => ['label' => 'Trabajo', 'rutas' => ['projects.index', 'tasks.index', 'clients.index']],
        'equipo' => ['label' => 'Equipo', 'rutas' => ['employees.index', 'areas.index']],
        'conocimiento' => ['label' => 'Conocimiento', 'rutas' => ['rag-documents.index', 'rag-query.index', 'normativa.index']],
    ];

    $espacios = \App\Support\WorkspaceAccess::areasFor(auth()->user());
    $puedeVerActividad = $porRuta->has('activity.index');

    // Apartado que contiene la página actual: siempre se muestra abierto.
    $activoEn = null;
    foreach ($apartados as $clave => $ap) {
        foreach ($ap['rutas'] as $ruta) {
            if ($porRuta->has($ruta) && request()->routeIs($porRuta[$ruta]['pattern'])) {
                $activoEn = $clave;
            }
        }
    }
    if (request()->is('espacios/*')) {
        $activoEn = 'areas';
    } elseif (request()->routeIs('activity.*') || request()->routeIs('alerts.*')) {
        $activoEn = 'sistema';
    }

    $claseItem = fn (bool $activo) => $activo
        ? 'bg-white/10 text-white'
        : 'text-slate-400 hover:bg-white/[0.06] hover:text-white';
@endphp

<div x-data="{
        mobileOpen: false,
        collapsed: localStorage.getItem('sidebar-collapsed') === '1',
        grupos: (() => { try { return JSON.parse(localStorage.getItem('sidebar-groups') || '{}'); } catch (e) { return {}; } })(),
        activo: @js($activoEn),
        tip: { show: false, text: '', y: 0 },
        abierto(clave, porDefecto) {
            if (clave === this.activo) return true;
            return clave in this.grupos ? this.grupos[clave] : porDefecto;
        },
        alternar(clave, porDefecto) {
            this.grupos[clave] = !this.abierto(clave, porDefecto);
            localStorage.setItem('sidebar-groups', JSON.stringify(this.grupos));
        },
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0');
            document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
            this.tip.show = false;
        },
        mostrarTip(e, texto) {
            if (!this.collapsed) return;
            const r = e.currentTarget.getBoundingClientRect();
            this.tip = { show: true, text: texto, y: r.top + r.height / 2 };
        },
    }"
    x-init="$watch('mobileOpen', (value) => document.body.classList.toggle('overflow-y-hidden', value))"
    @keydown.window="if ($event.key === '[' && !$event.ctrlKey && !$event.metaKey && !['INPUT','TEXTAREA','SELECT'].includes($event.target.tagName) && !$event.target.isContentEditable) toggle()">

    {{-- Barra superior en móvil --}}
    <div class="lg:hidden flex items-center justify-between bg-slate-900 text-white px-4 py-3">
        <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight min-h-[44px] inline-flex items-center">{{ config('app.name') }}</a>
        <button @click="mobileOpen = true" class="p-2 -mr-2 min-w-[44px] min-h-[44px] inline-flex items-center justify-center" aria-label="{{ __('Abrir menú') }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
    </div>
    <div x-show="mobileOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" @click="mobileOpen = false"></div>

    <aside
        x-show="true"
        :class="[
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            collapsed ? 'lg:!w-[4.75rem]' : '',
        ]"
        class="fixed inset-y-0 left-0 z-50 w-64 h-screen bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-slate-200 flex flex-col transition-[width,transform] duration-300 ease-[cubic-bezier(.2,.8,.2,1)] lg:translate-x-0 lg:static lg:sticky lg:top-0 lg:z-auto"
    >
        {{-- Franja en el borde: aparece al pasar el cursor y permite ocultar / mostrar con un clic (atajo: tecla "[") --}}
        <button type="button" @click="toggle()" tabindex="-1"
                class="group hidden lg:block absolute inset-y-0 -right-2 w-4 z-10 cursor-pointer outline-none"
                :aria-label="collapsed ? @js(__('Expandir menú')) : @js(__('Ocultar menú'))">
            <span class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-0.5 rounded-full bg-transparent group-hover:bg-emerald-400/70 group-hover:shadow-[0_0_14px_rgba(52,211,153,.7)] transition-all duration-300"></span>
            <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-5 h-14 rounded-full bg-slate-800 text-emerald-300 ring-1 ring-emerald-400/40 shadow-xl flex items-center justify-center opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100 transition-all duration-300 ease-out">
                <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="collapsed && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </span>
        </button>

        {{-- Marca + botón de panel --}}
        <div class="shrink-0 border-b border-white/5 flex items-center gap-2 px-3 transition-all duration-300"
             :class="collapsed ? 'h-14 lg:h-auto lg:flex-col lg:py-3 lg:px-0 lg:gap-2' : 'h-14'">
            <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 min-w-0 flex-1" :class="collapsed && 'lg:flex-none'">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center font-extrabold text-white shadow-lg shadow-emerald-900/40 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-105 shrink-0">
                    {{ Str::of(config('app.name'))->substr(0, 1) }}
                </span>
                <span x-show="!collapsed" class="font-bold text-white tracking-tight truncate">{{ config('app.name') }}</span>
            </a>
            <button type="button" @click="toggle()"
                    class="hidden lg:inline-flex shrink-0 w-8 h-8 items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-white/10 active:scale-90 transition"
                    :aria-label="collapsed ? @js(__('Expandir menú')) : @js(__('Ocultar menú'))"
                    @mouseenter="mostrarTip($event, @js(__('Expandir menú')))" @mouseleave="tip.show = false"
                    :title="collapsed ? '' : @js(__('Ocultar menú  ( [ )'))">
                {{-- Icono de panel: la barra lateral se rellena cuando el menú está visible --}}
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="16" rx="3" />
                    <path stroke-linecap="round" d="M9.5 4v16" />
                    <rect x="3.9" y="4.9" width="5" height="14.2" rx="2" fill="currentColor" stroke="none" class="transition-opacity duration-300" :class="collapsed ? 'opacity-0' : 'opacity-60'" />
                </svg>
            </button>
        </div>

        {{-- Navegación: compacta para que quepa sin scroll --}}
        <nav class="flex-1 min-h-0 px-2.5 py-2 space-y-0.5 overflow-y-auto overflow-x-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @php $dashActivo = request()->routeIs('dashboard'); @endphp
            <a href="{{ route('dashboard') }}" @mouseenter="mostrarTip($event, @js(__('Dashboard')))" @mouseleave="tip.show = false"
               class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold transition-all duration-200 {{ $claseItem($dashActivo) }}"
               :class="collapsed && 'lg:justify-center lg:px-0'">
                @if ($dashActivo)<span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,.8)]"></span>@endif
                <svg class="w-[18px] h-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $dashActivo ? 'text-emerald-400' : '' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">{!! $icons['home'] !!}</svg>
                <span x-show="!collapsed" class="truncate">{{ __('Dashboard') }}</span>
            </a>

            @foreach ($apartados as $clave => $ap)
                @php $items = collect($ap['rutas'])->filter(fn ($r) => $porRuta->has($r))->map(fn ($r) => $porRuta[$r]); @endphp
                @continue($items->isEmpty())
                @include('components.layouts.partials.sidebar-group', ['clave' => $clave, 'etiqueta' => $ap['label'], 'items' => $items, 'icons' => $icons, 'claseItem' => $claseItem, 'porDefecto' => 'window.innerHeight >= 780'])
            @endforeach

            @if ($espacios !== [])
                <div x-data="{ clave: 'areas' }">
                    <button type="button" x-show="!collapsed" @click="alternar('areas', true)"
                            class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 hover:text-slate-300 transition">
                        {{ __('Áreas de trabajo') }}
                        <svg class="w-3 h-3 transition-transform duration-300" :class="abierto('areas', true) ? 'rotate-0' : '-rotate-90'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="collapsed" x-cloak class="mx-3 my-2 border-t border-white/10"></div>
                    <div x-show="collapsed || abierto('areas', true)" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-0.5">
                        @foreach ($espacios as $clave => $etiqueta)
                            @php $tema = \App\Support\WorkspaceAccess::tema($clave); $activo = request()->is('espacios/'.$clave); @endphp
                            <a href="{{ route('workspaces.show', $clave) }}" @mouseenter="mostrarTip($event, @js($etiqueta))" @mouseleave="tip.show = false"
                               class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold transition-all duration-200 {{ $claseItem($activo) }}"
                               :class="collapsed && 'lg:justify-center lg:px-0'">
                                @if ($activo)<span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full {{ $tema['dot'] }}"></span>@endif
                                <span class="w-[18px] h-[18px] shrink-0 flex items-center justify-center">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $tema['dot'] }} ring-4 ring-white/5 transition-transform duration-200 group-hover:scale-150 {{ $activo ? 'scale-125' : '' }}"></span>
                                </span>
                                <span x-show="!collapsed" class="truncate">{{ $etiqueta }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Sistema: actividad y alertas --}}
            <div>
                <button type="button" x-show="!collapsed" @click="alternar('sistema', true)"
                        class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 hover:text-slate-300 transition">
                    {{ __('Sistema') }}
                    <svg class="w-3 h-3 transition-transform duration-300" :class="abierto('sistema', true) ? 'rotate-0' : '-rotate-90'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div x-show="collapsed" x-cloak class="mx-3 my-2 border-t border-white/10"></div>
                <div x-show="collapsed || abierto('sistema', true)" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-0.5">
                    @if ($puedeVerActividad)
                        @php $item = $porRuta['activity.index']; $activo = request()->routeIs($item['pattern']); @endphp
                        <a href="{{ route($item['route']) }}" @mouseenter="mostrarTip($event, @js(__($item['label'])))" @mouseleave="tip.show = false"
                           class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold transition-all duration-200 {{ $claseItem($activo) }}"
                           :class="collapsed && 'lg:justify-center lg:px-0'">
                            @if ($activo)<span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,.8)]"></span>@endif
                            <svg class="w-[18px] h-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $activo ? 'text-emerald-400' : '' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">{!! $icons[$item['icon']] !!}</svg>
                            <span x-show="!collapsed" class="truncate">{{ __($item['label']) }}</span>
                        </a>
                    @endif

                    @php $alertasActivo = request()->routeIs('alerts.*'); @endphp
                    <a href="{{ route('alerts.index') }}" @mouseenter="mostrarTip($event, @js(__('Alertas')))" @mouseleave="tip.show = false"
                       x-data="{ count: {{ $unreadAlerts }} }"
                       x-init="setInterval(() => {
                           fetch(@js(route('alerts.unread-count')), { headers: { 'Accept': 'application/json' } })
                               .then(res => res.ok ? res.json() : null)
                               .then(data => { if (data) count = data.count; })
                               .catch(() => {});
                       }, 20000)"
                       class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold transition-all duration-200 {{ $claseItem($alertasActivo) }}"
                       :class="collapsed && 'lg:justify-center lg:px-0'">
                        @if ($alertasActivo)<span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,.8)]"></span>@endif
                        <span class="relative shrink-0">
                            <svg class="w-[18px] h-[18px] transition-transform duration-200 group-hover:scale-110 group-hover:rotate-12 {{ $alertasActivo ? 'text-emerald-400' : '' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">{!! $icons['bell'] !!}</svg>
                            <span x-show="count > 0 && collapsed" x-cloak class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-slate-900 animate-pulse"></span>
                        </span>
                        <span x-show="!collapsed" class="flex-1 truncate">{{ __('Alertas') }}</span>
                        <span x-show="count > 0 && !collapsed" x-cloak x-text="count > 99 ? '99+' : count"
                              class="bg-rose-500 text-white text-[11px] font-bold rounded-full px-2 py-0.5 min-w-[1.25rem] text-center animate-pulse"></span>
                    </a>
                </div>
            </div>
        </nav>

        {{-- Usuario --}}
        <div class="border-t border-white/5 p-2.5 shrink-0">
            <x-ui.dropdown align="top" width="56">
                <x-slot name="trigger">
                    <button class="w-full flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-white/[0.06] transition-colors duration-200 text-left min-h-[44px]" :class="collapsed && 'lg:justify-center lg:px-0'" aria-label="{{ __('Cuenta de usuario') }}">
                        <span class="relative shrink-0">
                            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-sm font-bold text-white">{{ Str::of(Auth::user()->name)->substr(0, 1) }}</span>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 ring-2 ring-slate-900"></span>
                        </span>
                        <span x-show="!collapsed" class="min-w-0 flex-1">
                            <span class="block text-[13px] font-semibold text-white truncate">{{ Auth::user()->name }}</span>
                            <span class="block text-[11px] text-slate-400 truncate">{{ Auth::user()->email }}</span>
                        </span>
                        <svg x-show="!collapsed" class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-ui.dropdown-link :href="route('profile.edit')">{{ __('Perfil') }}</x-ui.dropdown-link>
                    @if (auth()->user()->hasRole(\App\Support\Enums\RoleName::Administrator->value))
                        <x-ui.dropdown-link :href="route('settings.company-signature.edit')">{{ __('Firma del Gerente General') }}</x-ui.dropdown-link>
                    @endif
                    <x-ui.dropdown-link :href="route('legal.security-policy')">{{ __('Políticas de seguridad y cookies') }}</x-ui.dropdown-link>
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

    {{-- Tooltip del modo compacto --}}
    <div x-show="tip.show && collapsed" x-cloak x-transition.opacity.duration.150ms
         class="hidden lg:block fixed left-[5.25rem] z-[70] -translate-y-1/2 px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-semibold shadow-xl ring-1 ring-white/10 pointer-events-none"
         :style="`top: ${tip.y}px`" x-text="tip.text"></div>
</div>
