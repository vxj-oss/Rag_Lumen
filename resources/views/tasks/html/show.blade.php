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

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('tasks.index') }}" class="inline-flex items-center min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    {{ __('← Volver al tablero') }}
                </a>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge :color="$task->state?->color ?? $task->estado->color()">{{ $task->state?->nombre ?? $task->estado->label() }}</x-ui.badge>
                    <span class="text-xs text-gray-400 self-center font-mono">{{ $task->codigo ?? '#'.$task->id }}</span>
                    <x-ui.badge :color="$task->prioridad->color()">{{ __('Prioridad') }}: {{ $task->prioridad->label() }}</x-ui.badge>
                    @if ($task->isOverdue())
                        <x-ui.badge color="red">{{ __('Atrasada') }}</x-ui.badge>
                    @endif
                </div>

                @if ($task->descripcion)
                    <p class="text-sm text-gray-700">{{ $task->descripcion }}</p>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        <h3 class="font-semibold text-gray-800">{{ __('Información') }}</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">{{ __('Proyecto') }}</dt>
                                <dd class="text-gray-900 text-right">
                                    @if ($task->project)
                                        <a href="{{ route('projects.show', $task->project) }}" class="text-emerald-600 hover:underline">{{ $task->project->nombre }}</a>
                                    @else
                                        {{ __('Proyecto archivado') }}
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">{{ __('Asignado a') }}</dt>
                                <dd class="text-gray-900 text-right">{{ $task->assignee?->fullName() ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">{{ __('Fecha de inicio') }}</dt>
                                <dd class="text-gray-900 text-right">{{ $task->fecha_inicio?->format('d/m/Y') ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">{{ __('Fecha límite') }}</dt>
                                <dd class="text-gray-900 text-right">{{ $task->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">{{ __('Horas (estimadas / reales)') }}</dt>
                                <dd class="text-gray-900 text-right">{{ $task->horas_estimadas ?? '—' }} / {{ $task->horas_reales ?? '—' }}</dd>
                            </div>
                            <div class="pt-3 border-t border-gray-100">
                                <dt class="text-gray-500 mb-1">{{ __('Avance') }}</dt>
                                <dd class="text-gray-900">
                                    <x-ui.progress-bar :value="$task->porcentaje_progreso" />
                                </dd>
                            </div>
                        </dl>

                        @if ($task->isBlockingState() && $task->motivo_bloqueo)
                            <div class="bg-red-50 border border-red-200 rounded-md p-3">
                                <dt class="text-sm font-medium text-red-800">{{ __('Motivo de bloqueo') }}</dt>
                                <dd class="text-sm text-red-700">{{ $task->motivo_bloqueo }}</dd>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-gray-100">
                            @if ($task->tareaPadre)
                                <p class="text-sm text-gray-500 mb-2">
                                    {{ __('Tarea padre') }}:
                                    <a href="{{ route('tasks.show', $task->tareaPadre) }}" class="text-emerald-600 hover:underline">{{ $task->tareaPadre->titulo }}</a>
                                </p>
                                @can('update', $task)
                                    <form action="{{ route('tasks.update', $task) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="titulo" value="{{ $task->titulo }}">
                                        <input type="hidden" name="prioridad" value="{{ $task->prioridad->value }}">
                                        <input type="hidden" name="tarea_padre_id" value="">
                                        <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Quitar de tarea padre') }}</button>
                                    </form>
                                @endcan
                            @elseif ($task->tieneSubtareas())
                                <p class="text-xs text-gray-400">{{ __('Esta tarea agrupa subtareas, así que no puede tener su propia tarea padre.') }}</p>
                            @elseif ($availableAsParent->isNotEmpty())
                                @can('update', $task)
                                    <form action="{{ route('tasks.update', $task) }}" method="POST" class="space-y-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="titulo" value="{{ $task->titulo }}">
                                        <input type="hidden" name="prioridad" value="{{ $task->prioridad->value }}">
                                        <x-forms.input-label for="tarea_padre_id" :value="__('Convertir en subtarea de')" />
                                        <div class="flex gap-2">
                                            <x-forms.select id="tarea_padre_id" name="tarea_padre_id" class="mt-1 block w-full text-sm"
                                                :options="['' => __('— Ninguna —')] + $availableAsParent->mapWithKeys(fn ($t) => [$t->id => $t->titulo])->toArray()" />
                                            <x-ui.secondary-button type="submit" class="mt-1 shrink-0">{{ __('Guardar') }}</x-ui.secondary-button>
                                        </div>
                                        <x-forms.input-error :messages="$errors->get('tarea_padre_id')" class="mt-1" />
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </div>

                    <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        <h3 class="font-semibold text-gray-800">{{ __('Depende de') }}</h3>

                        <ul class="divide-y divide-gray-100">
                            @forelse ($task->dependencies as $dependency)
                                <li class="py-2 flex items-center justify-between text-sm gap-2">
                                    <a href="{{ route('tasks.show', $dependency) }}" class="text-emerald-600 hover:underline truncate">{{ $dependency->titulo }}</a>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-ui.badge :color="$dependency->state?->color ?? $dependency->estado->color()">{{ $dependency->state?->nombre ?? $dependency->estado->label() }}</x-ui.badge>
                                        @can('update', $task)
                                            <form action="{{ route('tasks.dependencies.destroy', [$task, $dependency]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">{{ __('Quitar') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </li>
                            @empty
                                <li class="py-2 text-sm text-gray-500">{{ __('Esta tarea no depende de ninguna otra.') }}</li>
                            @endforelse
                        </ul>

                        @can('update', $task)
                            @if ($availableForDependency->isNotEmpty())
                                <form action="{{ route('tasks.dependencies.store', $task) }}" method="POST" class="space-y-3 pt-4 border-t border-gray-100">
                                    @csrf
                                    <div>
                                        <x-forms.input-label for="depends_on_task_id" :value="__('Agregar dependencia')" />
                                        <x-forms.select id="depends_on_task_id" name="depends_on_task_id" class="mt-1 block w-full"
                                            :options="$availableForDependency->mapWithKeys(fn ($t) => [$t->id => $t->titulo])" />
                                    </div>
                                    <x-ui.primary-button type="submit">{{ __('Agregar') }}</x-ui.primary-button>
                                </form>
                                @error('depends_on_task_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            @endif
                        @endcan

                        @if ($task->dependents->isNotEmpty())
                            <div class="pt-4 border-t border-gray-100">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">{{ __('Tareas que dependen de esta') }}</h4>
                                <ul class="divide-y divide-gray-100">
                                    @foreach ($task->dependents as $dependent)
                                        <li class="py-2 flex items-center justify-between text-sm gap-2">
                                            <a href="{{ route('tasks.show', $dependent) }}" class="text-emerald-600 hover:underline truncate">{{ $dependent->titulo }}</a>
                                            <x-ui.badge :color="$dependent->state?->color ?? $dependent->estado->color()">{{ $dependent->state?->nombre ?? $dependent->estado->label() }}</x-ui.badge>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">{{ __('Subtareas') }}</h3>
                            @if ($task->subtareas->isNotEmpty())
                                <span class="text-xs text-gray-400">{{ __(':count subtareas · :percent% completado', ['count' => $task->subtareas->count(), 'percent' => $task->porcentaje_progreso]) }}</span>
                            @endif
                        </div>

                        <ul class="divide-y divide-gray-100">
                            @forelse ($task->subtareas as $subtarea)
                                <li class="py-3 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <a href="{{ route('tasks.show', $subtarea) }}" class="text-emerald-600 hover:underline truncate font-medium">{{ $subtarea->titulo }}</a>
                                        <x-ui.badge :color="$subtarea->state?->color ?? $subtarea->estado->color()">{{ $subtarea->state?->nombre ?? $subtarea->estado->label() }}</x-ui.badge>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <div class="flex-1"><x-ui.progress-bar :value="$subtarea->porcentaje_progreso" /></div>
                                        <span class="text-xs text-gray-400 shrink-0">{{ $subtarea->assignee?->fullName() ?? __('Sin asignar') }}</span>
                                    </div>
                                </li>
                            @empty
                                <li class="py-2 text-sm text-gray-500">{{ __('Esta tarea todavía no tiene subtareas.') }}</li>
                            @endforelse
                        </ul>

                        @can('create', \App\Models\Task::class)
                            <form action="{{ route('tasks.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4 border-t border-gray-100">
                                @csrf
                                <input type="hidden" name="proyecto_id" value="{{ $task->proyecto_id }}">
                                <input type="hidden" name="tarea_padre_id" value="{{ $task->id }}">
                                <div class="sm:col-span-2">
                                    <x-forms.input-label for="sub_titulo" :value="__('Agregar subtarea')" />
                                    <x-forms.text-input id="sub_titulo" name="titulo" type="text" class="mt-1 block w-full" required
                                        placeholder="{{ __('Título de la subtarea') }}" />
                                    <x-forms.input-error :messages="$errors->get('titulo')" class="mt-1" />
                                </div>
                                <div>
                                    <x-forms.input-label for="sub_asignado" :value="__('Asignado a')" />
                                    <x-forms.select id="sub_asignado" name="asignado_a" class="mt-1 block w-full"
                                        :options="['' => __('— Sin asignar —')] + $employees->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()" />
                                </div>
                                <div>
                                    <x-forms.input-label for="sub_prioridad" :value="__('Prioridad')" />
                                    <x-forms.select id="sub_prioridad" name="prioridad" class="mt-1 block w-full"
                                        :options="collect($priorities)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
                                        :selected="\App\Support\Enums\Priority::Medium->value" />
                                </div>
                                <input type="hidden" name="estado_id" value="{{ $projectStates->firstWhere('es_inicial', true)?->id ?? $projectStates->first()?->id }}">
                                <div class="sm:col-span-2">
                                    <x-ui.primary-button type="submit">{{ __('Agregar subtarea') }}</x-ui.primary-button>
                                </div>
                            </form>
                        @endcan
                    </div>

                    @can('update', $task)
                        @if ($task->tieneSubtareas())
                            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800">
                                {{ __('El avance de esta tarea se calcula automáticamente a partir de sus :count subtareas.', ['count' => $task->subtareas->count()]) }}
                            </div>
                        @else
                            <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                                <h3 class="font-semibold text-gray-800">{{ __('Registrar avance') }}</h3>

                                <form action="{{ route('tasks.progress.store', $task) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-forms.input-label for="new_percentage" :value="__('Nuevo porcentaje de avance')" />
                                            <x-forms.text-input id="new_percentage" name="new_percentage" type="number" min="0" max="100" class="mt-1 block w-full"
                                                value="{{ $task->porcentaje_progreso }}" required />
                                            <x-forms.input-error :messages="$errors->get('new_percentage')" class="mt-2" />
                                        </div>
                                    </div>
                                    <div>
                                        <x-forms.input-label for="comment" :value="__('Comentario')" />
                                        <textarea id="comment" name="comment" rows="2" placeholder="{{ __('ej. Se terminó desktop y tablet. Falta adaptación mobile.') }}"
                                            class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm"></textarea>
                                        <x-forms.input-error :messages="$errors->get('comment')" class="mt-2" />
                                    </div>
                                    <x-ui.primary-button type="submit">{{ __('Registrar') }}</x-ui.primary-button>
                                </form>
                            </div>
                        @endif
                    @endcan

                    <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        <h3 class="font-semibold text-gray-800">{{ __('Historial de avances') }}</h3>

                        <ul class="divide-y divide-gray-100">
                            @forelse ($task->progressUpdates as $update)
                                <li class="py-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-900">{{ $update->porcentaje_anterior }}% → {{ $update->porcentaje_nuevo }}%</span>
                                        <span class="text-gray-400 text-xs">{{ $update->created_at->format('d/m/Y H:i') }} · {{ $update->user?->name ?? __('Sistema') }}</span>
                                    </div>
                                    @if ($update->comentario)
                                        <p class="text-gray-600 mt-1">{{ $update->comentario }}</p>
                                    @endif
                                </li>
                            @empty
                                <li class="py-2 text-sm text-gray-500">{{ __('Todavía no se ha registrado ningún avance.') }}</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        <h3 class="font-semibold text-gray-800">{{ __('Historial de movimientos de estado') }}</h3>

                        <ul class="divide-y divide-gray-100">
                            @forelse ($task->statusHistory as $movement)
                                <li class="py-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-900">
                                            {{ $movement->from?->nombre ?? __('Creación') }} → {{ $movement->to?->nombre ?? '—' }}
                                        </span>
                                        <span class="text-gray-400 text-xs">{{ $movement->created_at->format('d/m/Y H:i') }} · {{ $movement->user?->name ?? __('Sistema') }}</span>
                                    </div>
                                    @if ($movement->comentario)
                                        <p class="text-gray-600 mt-1">{{ $movement->comentario }}</p>
                                    @endif
                                </li>
                            @empty
                                <li class="py-2 text-sm text-gray-500">{{ __('Todavía no hay movimientos registrados.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('update', $task)
        <x-ui.form-modal name="edit-task" :title="__('Editar tarea')" :subtitle="$task->project?->nombre ?? __('Proyecto archivado')"
            :action="route('tasks.update', $task)" method="PUT" max-width="3xl">
            @include('tasks.html.partials.form')
        </x-ui.form-modal>

        @if ($errors->any())
            <div x-data x-init="$dispatch('open-modal', 'edit-task')"></div>
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
</x-app-layout>
