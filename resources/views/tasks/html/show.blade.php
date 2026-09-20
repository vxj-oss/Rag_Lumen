<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$task->title" :subtitle="($task->code ?? '#'.$task->id) . ' · ' . ($task->project?->name ?? __('Proyecto archivado'))">
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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('tasks.index') }}" class="inline-flex items-center min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    {{ __('← Volver al tablero') }}
                </a>
            </div>
            <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-6">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge :color="$task->state?->color ?? $task->status->color()">{{ $task->state?->name ?? $task->status->label() }}</x-ui.badge>
                    <span class="text-xs text-gray-400 self-center font-mono">{{ $task->code ?? '#'.$task->id }}</span>
                    <x-ui.badge :color="$task->priority->color()">{{ __('Prioridad') }}: {{ $task->priority->label() }}</x-ui.badge>
                    @if ($task->isOverdue())
                        <x-ui.badge color="red">{{ __('Atrasada') }}</x-ui.badge>
                    @endif
                </div>

                @if ($task->description)
                    <p class="text-sm text-gray-700">{{ $task->description }}</p>
                @endif

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Proyecto') }}</dt>
                        <dd class="text-sm text-gray-900">
                            @if ($task->project)
                                <a href="{{ route('projects.show', $task->project) }}" class="text-emerald-600 hover:underline">{{ $task->project->name }}</a>
                            @else
                                {{ __('Proyecto archivado') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Asignado a') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $task->assignee?->fullName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fecha de inicio') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $task->start_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fecha límite') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $task->due_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Avance') }}</dt>
                        <dd class="text-sm text-gray-900">
                            <x-ui.progress-bar :value="$task->progress_percentage" />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Horas (estimadas / reales)') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $task->estimated_hours ?? '—' }} / {{ $task->actual_hours ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($task->isBlockingState() && $task->blocked_reason)
                    <div class="bg-red-50 border border-red-200 rounded-md p-3">
                        <dt class="text-sm font-medium text-red-800">{{ __('Motivo de bloqueo') }}</dt>
                        <dd class="text-sm text-red-700">{{ $task->blocked_reason }}</dd>
                    </div>
                @endif
            </div>

            @can('update', $task)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                    <h3 class="font-semibold text-gray-800">{{ __('Registrar avance') }}</h3>

                    <form action="{{ route('tasks.progress.store', $task) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-forms.input-label for="new_percentage" :value="__('Nuevo porcentaje de avance')" />
                                <x-forms.text-input id="new_percentage" name="new_percentage" type="number" min="0" max="100" class="mt-1 block w-full"
                                    value="{{ $task->progress_percentage }}" required />
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
            @endcan

            <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                <h3 class="font-semibold text-gray-800">{{ __('Historial de avances') }}</h3>

                <ul class="divide-y divide-gray-100">
                    @forelse ($task->progressUpdates as $update)
                        <li class="py-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">{{ $update->previous_percentage }}% → {{ $update->new_percentage }}%</span>
                                <span class="text-gray-400 text-xs">{{ $update->created_at->format('d/m/Y H:i') }} · {{ $update->user?->name ?? __('Sistema') }}</span>
                            </div>
                            @if ($update->comment)
                                <p class="text-gray-600 mt-1">{{ $update->comment }}</p>
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
                                    {{ $movement->from?->name ?? __('Creación') }} → {{ $movement->to?->name ?? '—' }}
                                </span>
                                <span class="text-gray-400 text-xs">{{ $movement->created_at->format('d/m/Y H:i') }} · {{ $movement->user?->name ?? __('Sistema') }}</span>
                            </div>
                            @if ($movement->comment)
                                <p class="text-gray-600 mt-1">{{ $movement->comment }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-500">{{ __('Todavía no hay movimientos registrados.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                <h3 class="font-semibold text-gray-800">{{ __('Depende de') }}</h3>

                <ul class="divide-y divide-gray-100">
                    @forelse ($task->dependencies as $dependency)
                        <li class="py-2 flex items-center justify-between text-sm">
                            <a href="{{ route('tasks.show', $dependency) }}" class="text-emerald-600 hover:underline">{{ $dependency->title }}</a>
                            <div class="flex items-center gap-2">
                                <x-ui.badge :color="$dependency->state?->color ?? $dependency->status->color()">{{ $dependency->state?->name ?? $dependency->status->label() }}</x-ui.badge>
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
                        <form action="{{ route('tasks.dependencies.store', $task) }}" method="POST" class="flex flex-wrap items-end gap-3 pt-4 border-t border-gray-100">
                            @csrf
                            <div>
                                <x-forms.input-label for="depends_on_task_id" :value="__('Agregar dependencia')" />
                                <x-forms.select id="depends_on_task_id" name="depends_on_task_id" class="mt-1"
                                    :options="$availableForDependency->mapWithKeys(fn ($t) => [$t->id => $t->title])" />
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
                                <li class="py-2 flex items-center justify-between text-sm">
                                    <a href="{{ route('tasks.show', $dependent) }}" class="text-emerald-600 hover:underline">{{ $dependent->title }}</a>
                                    <x-ui.badge :color="$dependent->state?->color ?? $dependent->status->color()">{{ $dependent->state?->name ?? $dependent->status->label() }}</x-ui.badge>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('update', $task)
        <x-ui.form-modal name="edit-task" :title="__('Editar tarea')" :subtitle="$task->project?->name ?? __('Proyecto archivado')"
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
