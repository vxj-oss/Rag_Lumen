<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$task->titulo" :subtitle="($task->codigo ?? '#'.$task->id) . ' · ' . ($task->project?->nombre ?? __('Proyecto archivado'))">
            @can('update', $task)
                <x-slot name="actions">
                    <x-ui.secondary-button x-data @click="$dispatch('open-modal', 'edit-task')" class="min-h-[44px]">
                        {{ __('Editar') }}
                    </x-ui.secondary-button>
                </x-slot>
            @endcan
        </x-ui.page-header>
    </x-slot>

    @php
        // Recorrido de la actividad: Asignada → En progreso → 100 % en revisión → Aprobada.
        $pasoActual = match (true) {
            $task->estaAprobada() => 3,
            $task->enRevision() => 2,
            $task->porcentaje_progreso > 0 => 1,
            default => 0,
        };
        $pasos = [__('Asignada'), __('En progreso'), __('En revisión'), __('Aprobada')];
        $hechas = $task->subtareas->where('estado', \App\Support\Enums\TaskStatus::Completed)->count();
        $siguiente = $task->subtareas->first(fn ($s) => $s->estado !== \App\Support\Enums\TaskStatus::Completed);
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1 min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-semibold group">
                <span class="transition-transform group-hover:-translate-x-1">←</span> {{ __('Volver al tablero') }}
            </a>

            {{-- Cabecera: progreso, datos clave y recorrido --}}
            <section class="relative overflow-hidden bg-white rounded-3xl border border-slate-200/70 shadow-soft p-6 sm:p-8 space-y-6 animate-scale-in">
                <div aria-hidden="true" class="pointer-events-none absolute -top-20 -right-12 w-64 h-64 rounded-full bg-emerald-300/20 blur-3xl"></div>

                <div class="relative flex flex-col sm:flex-row sm:items-center gap-6">
                    <x-ui.ring :value="$task->porcentaje_progreso" :size="104" :stroke="9" />
                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge color="gray">{{ __('Actividad') }}</x-ui.badge>
                            <x-ui.badge :color="$task->state?->color ?? $task->estado->color()">{{ $task->state?->nombre ?? $task->estado->label() }}</x-ui.badge>
                            <x-ui.badge :color="$task->prioridad->color()">{{ __('Prioridad') }}: {{ $task->prioridad->label() }}</x-ui.badge>
                            @if ($task->isOverdue())
                                <x-ui.badge color="red">{{ __('Atrasada') }}</x-ui.badge>
                            @endif
                            <span class="text-xs text-slate-500 font-mono">{{ $task->codigo ?? '#'.$task->id }}</span>
                        </div>
                        @if ($task->descripcion)
                            <p class="text-slate-600">{{ $task->descripcion }}</p>
                        @endif
                        <div class="flex flex-wrap gap-2 text-sm">
                            @if ($task->project)
                                <a href="{{ route('projects.show', $task->project) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-50 hover:bg-emerald-50 px-3 py-1.5 text-slate-700 transition">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5M4.5 9.75V6a2.25 2.25 0 012.25-2.25h4.94a2.25 2.25 0 011.591.659l1.06 1.06a.75.75 0 00.531.22H19.5A2.25 2.25 0 0121.75 8v1.75" /></svg>
                                    {{ $task->project->nombre }}
                                </a>
                            @endif
                            <span class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-slate-700">
                                <x-ui.avatar :name="$task->assignee?->fullName() ?? '?'" size="w-5 h-5 text-[9px]" />
                                {{ $task->assignee?->fullName() ?? __('Sin asignar') }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-slate-700">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                {{ $task->fecha_inicio?->format('d/m') ?? '—' }} → {{ $task->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Recorrido --}}
                <ol class="relative flex items-start" aria-label="{{ __('Recorrido de la actividad') }}">
                    @foreach ($pasos as $i => $paso)
                        <li class="flex-1 flex flex-col items-center text-center relative">
                            @if (! $loop->first)
                                <span class="absolute top-4 right-1/2 w-full h-0.5 {{ $i <= $pasoActual ? 'bg-emerald-500' : 'bg-slate-200' }} transition-colors duration-700" aria-hidden="true"></span>
                            @endif
                            <span class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white transition-all duration-500
                                {{ $i < $pasoActual ? 'bg-emerald-500 text-white' : ($i === $pasoActual ? 'bg-emerald-600 text-white shadow-glow scale-110' : 'bg-slate-100 text-slate-500') }}">
                                @if ($i < $pasoActual || ($i === 3 && $pasoActual === 3))
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            <span class="mt-2 text-xs font-semibold {{ $i <= $pasoActual ? 'text-slate-800' : 'text-slate-500' }}">{{ $paso }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>

            <div class="grid grid-cols-1 gap-6 items-start">
                <div class="space-y-6">
                    @if ($task->motivo_rechazo && ! $task->enRevision() && ! $task->estaAprobada())
                        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 animate-fade-in-up">
                            <p class="text-sm font-bold text-rose-800">{{ __('Actividad rechazada: requiere corrección') }}</p>
                            <p class="text-sm text-rose-700 mt-1">{{ $task->motivo_rechazo }}</p>
                            <p class="text-xs text-rose-600 mt-2">{{ __('Corrige el trabajo y vuelve a completar cada tarea con su evidencia.') }}</p>
                        </div>
                    @endif

                    @if ($task->enRevision())
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-3 animate-fade-in-up">
                            <p class="text-sm text-amber-800">{{ __('Todas las tareas están completadas (100 %). La actividad está pendiente de revisión: revisa la evidencia de cada tarea y aprueba o rechaza.') }}</p>
                            @can('reviewEvidence', $task)
                                <div class="flex flex-wrap items-center gap-3">
                                    <form action="{{ route('tasks.evidence.approve', $task) }}" method="POST">
                                        @csrf
                                        <x-ui.primary-button type="submit">{{ __('Aprobar actividad') }}</x-ui.primary-button>
                                    </form>
                                    <button type="button" x-data @click="$dispatch('open-modal', 'reject-evidence')" class="text-sm text-rose-600 hover:underline font-semibold">
                                        {{ __('Rechazar actividad') }}
                                    </button>
                                </div>
                            @endcan
                        </div>
                    @elseif ($task->estaAprobada())
                        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 text-sm text-emerald-800 animate-fade-in-up">
                            {{ __('Actividad aprobada por el líder el :date.', ['date' => $task->completado_en?->format('d/m/Y H:i')]) }}
                        </div>
                    @endif

                    {{-- Tareas --}}
                    <div class="space-y-3" x-data="{ abierta: {{ $siguiente?->id ?? 'null' }} }">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="font-bold text-slate-900 text-lg">{{ __('Tareas') }}</h3>
                            @if ($task->subtareas->isNotEmpty())
                                <span class="text-xs font-semibold text-slate-500 bg-white border border-slate-200 rounded-full px-3 py-1 tabular-nums">{{ __(':done de :count completadas', ['done' => $hechas, 'count' => $task->subtareas->count()]) }} · {{ __('cada una vale :each %', ['each' => round(100 / $task->subtareas->count(), 1)]) }}</span>
                            @endif
                        </div>

                        <ul class="space-y-3 stagger">
                            @forelse ($task->subtareas as $subtarea)
                                @php $hecha = $subtarea->estado === \App\Support\Enums\TaskStatus::Completed; @endphp
                                <li id="tarea-{{ $subtarea->id }}" class="bg-white rounded-2xl border transition-colors duration-300 shadow-soft overflow-hidden {{ $hecha ? 'border-emerald-200' : 'border-slate-200/70' }}"
                                    x-data="{ get open() { return abierta === {{ $subtarea->id }} } }">
                                    <button type="button" @click="abierta = open ? null : {{ $subtarea->id }}" class="w-full flex items-center gap-4 p-4 text-left hover:bg-slate-50/70 transition">
                                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-extrabold text-sm transition-all duration-300 {{ $hecha ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500' }}">
                                            @if ($hecha)
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </span>
                                        <span class="flex-1 min-w-0">
                                            <span class="block font-semibold text-slate-900 truncate">{{ $subtarea->titulo }}</span>
                                            <span class="block text-xs text-slate-500">{{ $hecha ? __('Completada con evidencia') : __('Pendiente') }}@if ($subtarea->evidencias->isNotEmpty()) · {{ trans_choice(':count evidencia|:count evidencias', $subtarea->evidencias->count()) }}@endif</span>
                                        </span>
                                        <x-ui.badge :color="$hecha ? 'green' : 'gray'">{{ $hecha ? __('Completada') : __('Pendiente') }}</x-ui.badge>
                                        <svg class="w-5 h-5 text-slate-500 transition-transform duration-300 shrink-0" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                    </button>

                                    <div x-show="open" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                                        <div class="px-4 pb-5 pt-1 space-y-4 border-t border-slate-100">
                                            @if ($subtarea->descripcion)
                                                <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Qué hay que hacer') }}</p>
                                                    {{ $subtarea->descripcion }}
                                                </div>
                                            @endif

                                            @if ($subtarea->evidencias->isNotEmpty())
                                                <div class="space-y-2">
                                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Evidencia presentada') }}</p>
                                                    @foreach ($subtarea->evidencias as $evidencia)
                                                        <div class="flex items-start gap-3 rounded-xl border border-slate-100 p-3">
                                                            <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $evidencia->esArchivo() ? 'bg-sky-50 text-sky-600' : 'bg-violet-50 text-violet-600' }}">
                                                                @if ($evidencia->esArchivo())
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                                @else
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                                                                @endif
                                                            </span>
                                                            <div class="min-w-0 flex-1 text-sm">
                                                                @if ($evidencia->esArchivo())
                                                                    <a href="{{ route('tasks.evidence.download', [$subtarea, $evidencia]) }}" class="font-semibold text-emerald-700 hover:underline truncate block">{{ $evidencia->nombre_archivo }}</a>
                                                                @else
                                                                    <a href="{{ $evidencia->url }}" target="_blank" rel="noopener" class="font-semibold text-emerald-700 hover:underline truncate block">{{ $evidencia->url }}</a>
                                                                @endif
                                                                @if ($evidencia->comentario)
                                                                    <p class="text-slate-600 mt-0.5">{{ $evidencia->comentario }}</p>
                                                                @endif
                                                                <p class="text-xs text-slate-500 mt-1">{{ $evidencia->user?->name ?? __('Sistema') }} · {{ $evidencia->created_at->format('d/m/Y H:i') }}</p>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($subtarea->puedeRecibirEvidencia())
                                                @can('submitEvidence', $subtarea)
                                                    <form action="{{ route('tasks.evidence.store', $subtarea) }}" method="POST" enctype="multipart/form-data"
                                                          class="space-y-3 rounded-2xl bg-emerald-50/50 border border-emerald-100 p-4"
                                                          x-data="{ links: [''], arrastrando: false, nombres: [],
                                                                    actualizar(e) { this.nombres = [...e.target.files].map(f => f.name); } }">
                                                        @csrf
                                                        <p class="text-sm font-bold text-slate-900">{{ __('Completar esta tarea') }}</p>
                                                        <p class="text-xs text-slate-500 -mt-2">{{ __('Adjunta al menos un archivo, imagen o enlace. Sin evidencia la tarea no se completa.') }}</p>

                                                        <label class="block cursor-pointer rounded-xl border-2 border-dashed px-4 py-6 text-center transition"
                                                               :class="arrastrando ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 bg-white hover:border-emerald-400'"
                                                               @dragover.prevent="arrastrando = true" @dragleave.prevent="arrastrando = false"
                                                               @drop.prevent="arrastrando = false; $refs.archivos.files = $event.dataTransfer.files; nombres = [...$event.dataTransfer.files].map(f => f.name)">
                                                            <svg class="w-8 h-8 mx-auto text-emerald-500 mb-1" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                                            <span class="text-sm font-semibold text-slate-700">{{ __('Arrastra archivos o imágenes aquí') }}</span>
                                                            <span class="block text-xs text-slate-500">{{ __('o haz clic para elegirlos · PDF, Word, Excel, JPG, PNG, WEBP · máx. 10 MB') }}</span>
                                                            <input x-ref="archivos" name="archivos[]" type="file" multiple class="sr-only" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" @change="actualizar($event)">
                                                        </label>
                                                        <ul class="flex flex-wrap gap-2" x-show="nombres.length" x-cloak>
                                                            <template x-for="n in nombres" :key="n"><li class="text-xs bg-white border border-slate-200 rounded-full px-3 py-1 text-slate-600" x-text="n"></li></template>
                                                        </ul>

                                                        <div>
                                                            <x-forms.input-label :value="__('Enlaces (Drive, Figma, repositorio…)')" />
                                                            <template x-for="(link, index) in links" :key="index">
                                                                <div class="flex gap-2 mt-1">
                                                                    <input type="url" :name="'enlaces[' + index + ']'" x-model="links[index]" placeholder="https://drive.google.com/..."
                                                                           class="block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                                                                    <button type="button" @click="links.splice(index, 1)" x-show="links.length > 1" class="text-slate-500 hover:text-rose-600 shrink-0 px-2 text-sm">{{ __('Quitar') }}</button>
                                                                </div>
                                                            </template>
                                                            <button type="button" @click="links.push('')" class="text-xs font-semibold text-emerald-600 hover:underline mt-2">{{ __('+ Agregar otro enlace') }}</button>
                                                        </div>
                                                        <div>
                                                            <x-forms.input-label :value="__('Comentario (opcional)')" />
                                                            <textarea name="comentario" rows="2" class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm"></textarea>
                                                        </div>
                                                        @if ((int) old('_evidence_task') === $subtarea->id)
                                                            <x-forms.input-error :messages="array_merge($errors->get('archivos'), $errors->get('archivos.*'), $errors->get('enlaces.*'))" />
                                                        @endif
                                                        <input type="hidden" name="_evidence_task" value="{{ $subtarea->id }}">
                                                        <x-ui.primary-button type="submit">{{ __('Completar tarea con evidencia') }}</x-ui.primary-button>
                                                    </form>
                                                @endcan
                                            @endif

                                            @can('manageActivityTasks', $task)
                                                @if ($task->subtareas->count() > 1)
                                                    <form action="{{ route('tasks.destroy', $subtarea) }}" method="POST" onsubmit="return confirm('{{ __('¿Quitar esta tarea?') }}')" class="text-right">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs text-slate-500 hover:text-rose-600 font-semibold">{{ __('Quitar tarea') }}</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li><x-ui.card><x-ui.empty :title="__('Esta actividad todavía no tiene tareas')" /></x-ui.card></li>
                            @endforelse
                        </ul>

                        @can('manageActivityTasks', $task)
                            <form action="{{ route('tasks.children.store', $task) }}" method="POST" class="bg-white rounded-2xl border border-dashed border-slate-300 p-4 space-y-3" x-data="{ abierto: {{ $errors->has('titulo') ? 'true' : 'false' }} }">
                                @csrf
                                <button type="button" @click="abierto = !abierto" class="flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-800">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-50 flex items-center justify-center text-base leading-none transition-transform" :class="abierto && 'rotate-45'">+</span>
                                    {{ __('Agregar tarea') }}
                                </button>
                                <div x-show="abierto" x-cloak x-transition class="space-y-3">
                                    <x-forms.text-input name="titulo" type="text" class="block w-full" required placeholder="{{ __('Título de la tarea') }}" />
                                    <x-forms.input-error :messages="$errors->get('titulo')" class="mt-1" />
                                    <textarea name="descripcion" rows="2" placeholder="{{ __('Descripción de lo que debe hacer el trabajador') }}"
                                        class="block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm"></textarea>
                                    <x-ui.primary-button type="submit">{{ __('Agregar tarea') }}</x-ui.primary-button>
                                </div>
                            </form>
                        @endcan
                    </div>

                    <div class="bg-white p-6 shadow-soft rounded-2xl border border-slate-200/70 space-y-4">
                        <h3 class="font-semibold text-slate-800">{{ __('Historial de avances') }}</h3>

                        <ul class="divide-y divide-slate-100">
                            @forelse ($task->progressUpdates as $update)
                                <li class="py-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-slate-900">{{ $update->porcentaje_anterior }}% → {{ $update->porcentaje_nuevo }}%</span>
                                        <span class="text-slate-500 text-xs">{{ $update->created_at->format('d/m/Y H:i') }} · {{ $update->user?->name ?? __('Sistema') }}</span>
                                    </div>
                                    @if ($update->comentario)
                                        <p class="text-slate-600 mt-1">{{ $update->comentario }}</p>
                                    @endif
                                </li>
                            @empty
                                <li class="py-2 text-sm text-slate-500">{{ __('Todavía no se ha registrado ningún avance.') }}</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="bg-white p-6 shadow-soft rounded-2xl border border-slate-200/70 space-y-4">
                        <h3 class="font-semibold text-slate-800">{{ __('Historial de movimientos de estado') }}</h3>

                        <ul class="divide-y divide-slate-100">
                            @forelse ($task->statusHistory as $movement)
                                <li class="py-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-slate-900">
                                            {{ $movement->from?->nombre ?? __('Creación') }} → {{ $movement->to?->nombre ?? '—' }}
                                        </span>
                                        <span class="text-slate-500 text-xs">{{ $movement->created_at->format('d/m/Y H:i') }} · {{ $movement->user?->name ?? __('Sistema') }}</span>
                                    </div>
                                    @if ($movement->comentario)
                                        <p class="text-slate-600 mt-1">{{ $movement->comentario }}</p>
                                    @endif
                                </li>
                            @empty
                                <li class="py-2 text-sm text-slate-500">{{ __('Todavía no hay movimientos registrados.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('update', $task)
        <x-ui.form-modal name="edit-task" :title="$task->esActividad() ? __('Editar actividad') : __('Editar tarea')" :subtitle="$task->project?->nombre ?? __('Proyecto archivado')"
            :action="route('tasks.update', $task)" method="PUT" max-width="3xl">
            @include('tasks.partials.form')
        </x-ui.form-modal>

        @if ($errors->any() && ! $errors->has('motivo'))
            <div x-data x-init="$dispatch('open-modal', 'edit-task')"></div>
        @endif
    @endcan

    @can('reviewEvidence', $task)
        <x-ui.form-modal name="reject-evidence" :title="__('Rechazar actividad')" :subtitle="$task->titulo"
            :action="route('tasks.evidence.reject', $task)" submit-label="{{ __('Rechazar') }}">
            <div>
                <x-forms.input-label for="motivo" :value="__('Motivo del rechazo (el trabajador deberá corregir y volver a presentar)')" />
                <textarea id="motivo" name="motivo" rows="3" required
                    class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm">{{ old('motivo') }}</textarea>
                <x-forms.input-error :messages="$errors->get('motivo')" class="mt-2" />
            </div>
        </x-ui.form-modal>

        @if ($errors->has('motivo'))
            <div x-data x-init="$dispatch('open-modal', 'reject-evidence')"></div>
        @endif
    @endcan

    <script>
        (function () {
            let lastSignature = null;
            let navigating = false;
            let pollInterval = null;
            let requestController = null;

            function stopPolling() {
                navigating = true;

                if (pollInterval !== null) {
                    window.clearInterval(pollInterval);
                    pollInterval = null;
                }

                requestController?.abort();
                requestController = null;
            }

            function userIsEditing() {
                const active = document.activeElement;
                if (!active) return false;
                const tag = active.tagName;
                return tag === 'TEXTAREA' || tag === 'SELECT'
                    || (tag === 'INPUT' && !['submit', 'button', 'checkbox'].includes(active.type));
            }

            async function poll() {
                if (navigating || document.hidden || requestController) return;
                if (document.body.classList.contains('overflow-y-hidden') || userIsEditing()) return;

                const controller = new AbortController();
                requestController = controller;

                try {
                    const response = await fetch(@js(route('tasks.live-status.one', $task)), {
                        headers: { 'Accept': 'application/json' },
                        signal: controller.signal,
                    });
                    if (!response.ok) return;

                    const data = await response.json();
                    if (controller.signal.aborted || navigating) return;

                    if (lastSignature === null) {
                        lastSignature = data.signature;
                        return;
                    }

                    if (data.signature !== lastSignature) {
                        stopPolling();
                        window.location.reload();
                    }
                } catch (e) {
                    if (e.name !== 'AbortError') return;
                } finally {
                    if (requestController === controller) requestController = null;
                }
            }

            document.addEventListener('click', (event) => {
                const link = event.target.closest?.('a[href]');
                if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                if (link.target === '_blank' || link.hasAttribute('download')) return;

                const destination = new URL(link.href, window.location.href);
                if (destination.origin !== window.location.origin || destination.href === window.location.href) return;

                stopPolling();
            }, true);
            window.addEventListener('pagehide', stopPolling, { once: true });

            pollInterval = window.setInterval(poll, 7000);
        })();
    </script>
    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8"><x-ui.card><h3 class="workspace-section-title">{{ __('Comentarios del equipo') }}</h3><p class="mt-1 text-xs text-slate-600">{{ __('Últimos 30 comentarios. Las evidencias y decisiones de revisión conservan su historial por separado.') }}</p><form method="POST" action="{{ route('tasks.comments',$task) }}" class="my-4 space-y-3" data-submit-feedback>@csrf<label class="sr-only" for="task-comment">{{ __('Nuevo comentario') }}</label><textarea id="task-comment" name="comment" required maxlength="450" rows="3" class="w-full rounded-xl border-slate-300" placeholder="Coordina una entrega o deja una observación…">{{ old('comment') }}</textarea>@error('comment')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror<x-ui.primary-button>{{ __('Publicar comentario') }}</x-ui.primary-button></form><ol class="divide-y divide-slate-100">@forelse($comments as $comment)<li class="py-3"><p class="text-xs font-semibold text-slate-600">{{ $comment->user?->name ?? __('Sistema') }} · {{ $comment->created_at->format('d/m/Y H:i') }}</p><p class="mt-1 whitespace-pre-wrap break-words text-sm text-slate-800">{{ $comment->descripcion }}</p></li>@empty<li class="py-4 text-sm text-slate-600">{{ __('Todavía no hay comentarios.') }}</li>@endforelse</ol></x-ui.card></div>
</x-app-layout>
