<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $task->title }}
            </h2>
            @can('update', $task)
                <x-ui.secondary-button x-data @click="$dispatch('open-modal', 'edit-task')">
                    {{ __('Editar') }}
                </x-ui.secondary-button>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-6">
                <div class="flex gap-2">
                    <x-ui.badge :color="$task->status->color()">{{ $task->status->label() }}</x-ui.badge>
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
                            <a href="{{ route('projects.show', $task->project) }}" class="text-emerald-600 hover:underline">{{ $task->project->name }}</a>
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

                @if ($task->status->value === 'blocked' && $task->blocked_reason)
                    <div class="bg-red-50 border border-red-200 rounded-md p-3">
                        <dt class="text-sm font-medium text-red-800">{{ __('Motivo de bloqueo') }}</dt>
                        <dd class="text-sm text-red-700">{{ $task->blocked_reason }}</dd>
                    </div>
                @endif

                <div>
                    <a href="{{ route('tasks.index') }}" class="text-emerald-600 hover:underline text-sm">
                        {{ __('← Volver al listado') }}
                    </a>
                </div>
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
                <h3 class="font-semibold text-gray-800">{{ __('Depende de') }}</h3>

                <ul class="divide-y divide-gray-100">
                    @forelse ($task->dependencies as $dependency)
                        <li class="py-2 flex items-center justify-between text-sm">
                            <a href="{{ route('tasks.show', $dependency) }}" class="text-emerald-600 hover:underline">{{ $dependency->title }}</a>
                            <div class="flex items-center gap-2">
                                <x-ui.badge :color="$dependency->status->color()">{{ $dependency->status->label() }}</x-ui.badge>
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
                                    <x-ui.badge :color="$dependent->status->color()">{{ $dependent->status->label() }}</x-ui.badge>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('update', $task)
        <x-ui.form-modal name="edit-task" :title="__('Editar tarea')" :subtitle="$task->project->name"
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

            function userIsEditing() {
                const active = document.activeElement;
                if (!active) return false;
                const tag = active.tagName;
                return tag === 'TEXTAREA' || tag === 'SELECT'
                    || (tag === 'INPUT' && !['submit', 'button', 'checkbox'].includes(active.type));
            }

            async function poll() {
                if (document.body.classList.contains('overflow-y-hidden') || userIsEditing()) return;

                try {
                    const response = await fetch(@js(route('tasks.live-status.one', $task)), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!response.ok) return;

                    const data = await response.json();

                    if (lastSignature === null) {
                        lastSignature = data.signature;
                        return;
                    }

                    if (data.signature !== lastSignature) {
                        window.location.reload();
                    }
                } catch (e) {
                }
            }

            setInterval(poll, 7000);
        })();
    </script>
</x-app-layout>
