<x-app-layout>
    @php
        $tabs = [
            'resumen' => ['label' => 'Resumen', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />', 'count' => null],
            'equipo' => ['label' => 'Equipo', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />', 'count' => $members->count()],
            'actividades' => ['label' => 'Actividades', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />', 'count' => $totalActividades],
            'normativa' => ['label' => 'TDR y faltas', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />', 'count' => $faltas->count()],
            'proyectos' => ['label' => 'Proyectos', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5M4.5 9.75V6a2.25 2.25 0 012.25-2.25h4.94a2.25 2.25 0 011.591.659l1.06 1.06a.75.75 0 00.531.22H19.5A2.25 2.25 0 0121.75 8v1.75" />', 'count' => $proyectos->count()],
        ];
        $areaLabel = $areas[$area];
        $gravedadColor = ['leve' => 'gray', 'grave' => 'yellow', 'muy_grave' => 'red'];
    @endphp

    <div class="py-6" x-data="{
            tab: ['resumen','equipo','actividades','normativa','proyectos'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'resumen',
            go(t) { this.tab = t; history.replaceState(null, '', '#' + t); },
        }" @hashchange.window="if (['resumen','equipo','actividades','normativa','proyectos'].includes(location.hash.slice(1))) tab = location.hash.slice(1)">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Cabecera del espacio --}}
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br {{ $tema['gradient'] }} text-white shadow-xl animate-scale-in">
                <div aria-hidden="true" class="absolute -top-24 -right-16 w-72 h-72 rounded-full bg-white/15 blur-3xl animate-float"></div>
                <div aria-hidden="true" class="absolute -bottom-28 left-1/4 w-80 h-80 rounded-full bg-black/10 blur-3xl"></div>
                <div class="relative p-6 sm:p-8 space-y-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur ring-1 ring-white/30 flex items-center justify-center shrink-0">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">{!! $tema['icon'] !!}</svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold tracking-[0.2em] uppercase text-white/80">{{ __('Espacio de área') }}</p>
                                <h1 class="text-3xl font-extrabold tracking-tight">{{ $areaLabel }}</h1>
                                <p class="text-sm text-white/85 truncate">{{ $tdr->nombre }}</p>
                            </div>
                        </div>

                        @if (count($areas) > 1)
                            <nav class="flex gap-1 p-1 rounded-2xl bg-black/15 backdrop-blur" aria-label="{{ __('Cambiar de área') }}">
                                @foreach ($areas as $clave => $etiqueta)
                                    <a href="{{ route('workspaces.show', $clave) }}"
                                       class="px-3 py-1.5 rounded-xl text-sm font-semibold transition {{ $clave === $area ? 'bg-white text-slate-900 shadow' : 'text-white/85 hover:bg-white/15' }}">{{ $etiqueta }}</a>
                                @endforeach
                            </nav>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 stagger">
                        @foreach ([
                            [__('Colaboradores'), $kpis['miembros']],
                            [__('Actividades abiertas'), $kpis['abiertas']],
                            [__('Pendientes de revisión'), $kpis['revision']],
                            [__('Atrasadas'), $kpis['atrasadas']],
                        ] as [$etiqueta, $valor])
                            <div class="rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/25 px-4 py-3">
                                <p class="text-3xl font-extrabold tabular-nums"
                                   x-data="{ n: 0 }" x-init="let t = {{ (int) $valor }}; let i = 0; const s = Math.max(1, Math.ceil(t / 20)); const id = setInterval(() => { n = Math.min(t, n + s); if (n >= t) clearInterval(id); }, 30)" x-text="n">{{ $valor }}</p>
                                <p class="text-xs text-white/85">{{ $etiqueta }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Pestañas --}}
            <div class="sticky top-0 z-20 -mx-4 px-4 sm:mx-0 sm:px-0 py-2 bg-slate-50/80 backdrop-blur" role="tablist">
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach ($tabs as $clave => $t)
                        <button type="button" role="tab" @click="go('{{ $clave }}')" :aria-selected="tab === '{{ $clave }}'"
                                :class="tab === '{{ $clave }}' ? @js($tema['tab']) : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-slate-900'"
                                class="tab-pill inline-flex items-center gap-2 px-4 py-2 min-h-[44px] rounded-xl text-sm font-semibold whitespace-nowrap active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!! $t['icon'] !!}</svg>
                            {{ __($t['label']) }}
                            @if ($t['count'] !== null)
                                <span class="text-[11px] px-1.5 py-0.5 rounded-full tabular-nums" :class="tab === '{{ $clave }}' ? 'bg-white/25' : 'bg-slate-100 text-slate-500'">{{ $t['count'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- RESUMEN --}}
            <section x-show="tab === 'resumen'" x-cloak x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <x-ui.card class="lg:col-span-2 space-y-5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">{{ __('Estado de las actividades') }}</h3>
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <x-ui.ring :value="$kpis['avance']" :size="48" :stroke="5" :color="$tema['ring']" />
                            <span>{{ __('avance promedio') }}</span>
                        </div>
                    </div>

                    @if ($totalActividades > 0)
                        <div class="flex h-3 rounded-full overflow-hidden bg-slate-100">
                            @foreach ($board as $clave => $col)
                                @php $ancho = round($col['items']->count() / $totalActividades * 100, 1); @endphp
                                <div class="h-full transition-all duration-700 {{ ['asignada' => 'bg-slate-300', 'progreso' => 'bg-sky-400', 'revision' => 'bg-amber-400', 'aprobada' => 'bg-emerald-500'][$clave] }}" style="width: {{ $ancho }}%" title="{{ $col['label'] }}: {{ $col['items']->count() }}"></div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach ($board as $clave => $col)
                                <button type="button" @click="go('actividades')" class="text-left rounded-xl p-3 border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full {{ ['asignada' => 'bg-slate-300', 'progreso' => 'bg-sky-400', 'revision' => 'bg-amber-400', 'aprobada' => 'bg-emerald-500'][$clave] }}"></span>
                                    <p class="text-2xl font-extrabold text-slate-900 tabular-nums">{{ $col['items']->count() }}</p>
                                    <p class="text-xs text-slate-500">{{ __($col['label']) }}</p>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty :title="__('Aún no hay actividades en esta área')" :text="__('Cuando se asignen actividades a los colaboradores, verás aquí su estado.')" />
                    @endif

                    <div>
                        <h4 class="text-sm font-bold text-slate-900 mb-3">{{ __('Carga por colaborador') }}</h4>
                        <div class="space-y-3">
                            @forelse ($members as $m)
                                @php $d = $porMiembro[$m->id]; @endphp
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$m->fullName()" size="w-8 h-8 text-xs" :tone="$tema['tone']" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="font-medium text-slate-800 truncate">{{ $m->fullName() }}</span>
                                            <span class="text-xs text-slate-500 tabular-nums">{{ $d['abiertas'] }} {{ __('abiertas') }}</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden mt-1">
                                            <div class="h-full rounded-full bg-gradient-to-r {{ $tema['gradient'] }} transition-all duration-700" style="width: {{ $d['avance'] }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600 tabular-nums w-10 text-right">{{ $d['avance'] }}%</span>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">{{ __('No hay colaboradores activos en esta área.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card class="space-y-4">
                    <h3 class="font-bold text-slate-900">{{ __('Próximas entregas') }}</h3>
                    <ul class="space-y-2">
                        @forelse ($proximas as $t)
                            @php $atrasada = $t->fecha_vencimiento->isPast(); @endphp
                            <li>
                                <a href="{{ route('tasks.show', $t) }}" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition group">
                                    <div class="w-11 h-11 rounded-xl flex flex-col items-center justify-center shrink-0 {{ $atrasada ? 'bg-rose-50 text-rose-600' : $tema['soft'].' '.$tema['text'] }}">
                                        <span class="text-sm font-extrabold leading-none">{{ $t->fecha_vencimiento->format('d') }}</span>
                                        <span class="text-[10px] uppercase font-semibold">{{ $t->fecha_vencimiento->translatedFormat('M') }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-emerald-700">{{ $t->titulo }}</p>
                                        <p class="text-xs text-slate-500 truncate">{{ $t->assignee?->fullName() }} · {{ $t->project?->nombre }}</p>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li><x-ui.empty :title="__('Sin entregas próximas')" /></li>
                        @endforelse
                    </ul>
                </x-ui.card>
            </section>

            {{-- EQUIPO --}}
            <section x-show="tab === 'equipo'" x-cloak x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                @if ($members->isEmpty())
                    <x-ui.card><x-ui.empty :title="__('Sin colaboradores en esta área')" :text="__('Se agregan según la especialidad del perfil de cada empleado.')" /></x-ui.card>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 stagger">
                        @foreach ($members as $m)
                            @php $d = $porMiembro[$m->id]; @endphp
                            <x-ui.card :interactive="true" class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$m->fullName()" size="w-12 h-12 text-base" :tone="$tema['tone']" />
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-slate-900 truncate">{{ $m->fullName() }}</p>
                                        <p class="text-sm text-slate-500 truncate">{{ $m->cargo ?? '—' }}</p>
                                        <p class="text-xs {{ $tema['text'] }} font-semibold">{{ $m->especialidad?->label() }}</p>
                                    </div>
                                    <x-ui.ring :value="$d['avance']" :size="52" :stroke="5" :color="$tema['ring']" />
                                </div>
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-xl bg-slate-50 py-2"><p class="text-lg font-extrabold text-slate-900 tabular-nums">{{ $d['abiertas'] }}</p><p class="text-[11px] text-slate-500">{{ __('Abiertas') }}</p></div>
                                    <div class="rounded-xl bg-amber-50 py-2"><p class="text-lg font-extrabold text-amber-700 tabular-nums">{{ $d['revision'] }}</p><p class="text-[11px] text-amber-700/80">{{ __('En revisión') }}</p></div>
                                    <div class="rounded-xl {{ $d['atrasadas'] > 0 ? 'bg-rose-50' : 'bg-slate-50' }} py-2"><p class="text-lg font-extrabold {{ $d['atrasadas'] > 0 ? 'text-rose-700' : 'text-slate-900' }} tabular-nums">{{ $d['atrasadas'] }}</p><p class="text-[11px] {{ $d['atrasadas'] > 0 ? 'text-rose-700/80' : 'text-slate-500' }}">{{ __('Atrasadas') }}</p></div>
                                </div>
                                <div class="flex items-center justify-between gap-2 pt-1">
                                    @php $acepto = $m->aceptoTdr($tdr); @endphp
                                    <x-ui.badge :color="$acepto ? 'green' : 'yellow'">{{ $acepto ? __('TDR aceptado') : __('TDR pendiente') }}</x-ui.badge>
                                    @can('view', $m)
                                        <a href="{{ route('employees.show', $m) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-800">{{ __('Ver perfil →') }}</a>
                                    @endcan
                                </div>
                            </x-ui.card>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ACTIVIDADES --}}
            <section x-show="tab === 'actividades'" x-cloak x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    @foreach ($board as $clave => $col)
                        @php $punto = ['asignada' => 'bg-slate-400', 'progreso' => 'bg-sky-500', 'revision' => 'bg-amber-500', 'aprobada' => 'bg-emerald-500'][$clave]; @endphp
                        <div class="rounded-2xl bg-slate-100/70 p-3 space-y-3 min-h-[8rem]">
                            <div class="flex items-center justify-between px-1">
                                <h4 class="flex items-center gap-2 text-sm font-bold text-slate-700"><span class="w-2.5 h-2.5 rounded-full {{ $punto }}"></span>{{ __($col['label']) }}</h4>
                                <span class="text-xs font-semibold text-slate-500 bg-white rounded-full px-2 py-0.5 tabular-nums">{{ $col['items']->count() }}</span>
                            </div>
                            <div class="space-y-3 stagger">
                                @forelse ($col['items'] as $t)
                                    @php $atrasada = $clave !== 'aprobada' && $t->fecha_vencimiento?->isPast(); @endphp
                                    <a href="{{ route('tasks.show', $t) }}" class="block bg-white rounded-xl p-3.5 border border-slate-200/70 shadow-sm lift space-y-2.5">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-semibold text-slate-900 leading-snug">{{ $t->titulo }}</p>
                                            <x-ui.badge :color="$t->prioridad->color()">{{ $t->prioridad->label() }}</x-ui.badge>
                                        </div>
                                        <p class="text-xs text-slate-500 truncate">{{ $t->project?->nombre }}</p>
                                        <x-ui.progress-bar :value="$t->porcentaje_progreso" bar-width="flex-1" class="w-full" />
                                        <div class="flex items-center justify-between text-xs text-slate-500">
                                            <span class="inline-flex items-center gap-1.5"><x-ui.avatar :name="$t->assignee?->fullName() ?? '?'" size="w-5 h-5 text-[9px]" :tone="$tema['tone']" />{{ $t->assignee?->nombres }}</span>
                                            <span class="tabular-nums">{{ $t->subtareas_completadas_count }}/{{ $t->subtareas_count }} {{ __('tareas') }}</span>
                                        </div>
                                        @if ($t->fecha_vencimiento)
                                            <p class="text-xs font-medium {{ $atrasada ? 'text-rose-600' : 'text-slate-400' }}">{{ $atrasada ? __('Atrasada · ') : __('Vence ') }}{{ $t->fecha_vencimiento->format('d/m/Y') }}</p>
                                        @endif
                                    </a>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-6">{{ __('Sin actividades') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- TDR Y FALTAS --}}
            <section x-show="tab === 'normativa'" x-cloak x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <x-ui.card class="xl:col-span-2 space-y-4" x-data="{ abiertas: [1], alternar(n) { this.abiertas = this.abiertas.includes(n) ? this.abiertas.filter(x => x !== n) : [...this.abiertas, n]; } }">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider {{ $tema['text'] }}">{{ __('TDR · versión :v', ['v' => $tdr->version]) }}</p>
                            <h3 class="font-bold text-slate-900">{{ $tdr->nombre }}</h3>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <button type="button" class="font-semibold text-slate-500 hover:text-slate-800" @click="abiertas = {{ json_encode(range(1, count($tdr->secciones()))) }}">{{ __('Expandir todo') }}</button>
                            <button type="button" class="font-semibold text-slate-500 hover:text-slate-800" @click="abiertas = []">{{ __('Contraer') }}</button>
                            <a href="{{ route('normativa.index', ['area' => $area]) }}" class="font-semibold text-emerald-600 hover:text-emerald-800">{{ auth()->user()->isAdmin() ? __('Editar') : __('Ver completo') }} →</a>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                        @foreach ($tdr->secciones() as $seccion)
                            <div>
                                <button type="button" @click="alternar({{ $loop->iteration }})" class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 transition">
                                    <span class="w-7 h-7 rounded-xl {{ $tema['soft'] }} {{ $tema['text'] }} text-xs font-extrabold flex items-center justify-center shrink-0">{{ $seccion['numero'] ?? $loop->iteration }}</span>
                                    <span class="flex-1 text-sm font-semibold text-slate-800">{{ $seccion['titulo'] }}</span>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-300" :class="abiertas.includes({{ $loop->iteration }}) && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="abiertas.includes({{ $loop->iteration }})" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="px-4 pb-4 pl-14 space-y-1.5 text-sm text-slate-600">
                                        @foreach ($seccion['lineas'] as $linea)
                                            @if ($linea['tipo'] === 'li')
                                                <p class="pl-4 relative"><span class="absolute left-0 {{ $tema['text'] }}">•</span>{{ $linea['texto'] }}</p>
                                            @else
                                                <p>{{ $linea['texto'] }}</p>
                                            @endif
                                        @endforeach

                                        @if (str_starts_with(mb_strtoupper($seccion['titulo']), 'ENTREGABLES') && ! empty($tdr->entregables))
                                            <div class="overflow-x-auto rounded-xl border border-slate-200 mt-2">
                                                <table class="min-w-full text-xs">
                                                    <thead class="bg-slate-50 text-slate-500 uppercase">
                                                        <tr><th class="px-3 py-2 text-left w-10">{{ __('N.°') }}</th><th class="px-3 py-2 text-left">{{ __('Entregable') }}</th><th class="px-3 py-2 text-left">{{ __('Plazo') }}</th></tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @foreach ($tdr->entregables as $e)
                                                            <tr><td class="px-3 py-2 font-bold {{ $tema['text'] }}">{{ $loop->iteration }}</td><td class="px-3 py-2">{{ $e['producto'] }}</td><td class="px-3 py-2 text-slate-500">{{ $e['plazo'] }}</td></tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <div class="space-y-6">
                    <x-ui.card class="space-y-3">
                        <h3 class="font-bold text-slate-900">{{ __('Aceptación del equipo') }}</h3>
                        <ul class="space-y-2">
                            @forelse ($members as $m)
                                @php $acepto = $m->aceptoTdr($tdr); @endphp
                                <li class="flex items-center gap-3">
                                    <x-ui.avatar :name="$m->fullName()" size="w-8 h-8 text-xs" :tone="$tema['tone']" />
                                    <span class="flex-1 text-sm text-slate-700 truncate">{{ $m->fullName() }}</span>
                                    <x-ui.badge :color="$acepto ? 'green' : 'yellow'">{{ $acepto ? __('Aceptó') : __('Pendiente') }}</x-ui.badge>
                                </li>
                            @empty
                                <li class="text-sm text-slate-500">{{ __('Sin colaboradores.') }}</li>
                            @endforelse
                        </ul>
                    </x-ui.card>

                    <x-ui.card class="space-y-3">
                        <h3 class="font-bold text-slate-900">{{ __('Manual de faltas') }}</h3>
                        <ul class="space-y-2">
                            @forelse ($faltas as $f)
                                <li class="rounded-xl border border-slate-100 p-3 space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-mono font-bold text-slate-500">{{ $f->codigo }}</span>
                                        <x-ui.badge :color="$f->gravedadColor()">{{ $f->gravedadLabel() }}</x-ui.badge>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $f->titulo }}</p>
                                    <p class="text-xs text-slate-500">{{ $f->sancion ?? '' }}{{ $f->monto !== null ? ' · S/ '.number_format($f->monto, 2) : '' }}</p>
                                </li>
                            @empty
                                <li><x-ui.empty :title="__('Sin faltas registradas')" /></li>
                            @endforelse
                        </ul>
                    </x-ui.card>
                </div>
            </section>

            {{-- PROYECTOS --}}
            <section x-show="tab === 'proyectos'" x-cloak x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                @if ($proyectos->isEmpty())
                    <x-ui.card><x-ui.empty :title="__('Sin proyectos activos en esta área')" :text="__('Aparecen los proyectos donde el equipo del área tiene actividades asignadas.')" /></x-ui.card>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 stagger">
                        @foreach ($proyectos as $fila)
                            @php $p = $fila['proyecto']; @endphp
                            <x-ui.card :interactive="true" class="space-y-4">
                                <div class="flex items-start gap-4">
                                    <x-ui.ring :value="$fila['avance']" :size="64" :stroke="6" :color="$tema['ring']" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-mono font-bold text-slate-400">{{ $p->codigo }}</p>
                                        <p class="font-bold text-slate-900 leading-snug">{{ $p->nombre }}</p>
                                        <p class="text-xs text-slate-500 truncate">{{ $p->client?->nombre ?? __('Sin cliente') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <x-ui.badge :color="$p->estado->color()">{{ $p->estado->label() }}</x-ui.badge>
                                    <span class="text-xs text-slate-500">{{ trans_choice(':count actividad|:count actividades', $fila['actividades']) }}</span>
                                </div>
                                @can('view', $p)
                                    <a href="{{ route('projects.show', $p) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-800">{{ __('Abrir proyecto →') }}</a>
                                @endcan
                            </x-ui.card>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
