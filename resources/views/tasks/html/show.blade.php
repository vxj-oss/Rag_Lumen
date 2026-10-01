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
                    <x-ui.badge color="gray">{{ __('Actividad') }}</x-ui.badge>
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
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    @if ($task->motivo_rechazo && ! $task->enRevision() && ! $task->estaAprobada())
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-sm font-semibold text-red-800">{{ __('Actividad rechazada: requiere corrección') }}</p>
                            <p class="text-sm text-red-700 mt-1">{{ $task->motivo_rechazo }}</p>
                            <p class="text-xs text-red-600 mt-2">{{ __('Corrige el trabajo y vuelve a completar cada tarea con su evidencia.') }}</p>
                        </div>
                    @endif

                    @if ($task->enRevision())
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-3">
                            <p class="text-sm text-amber-800">{{ __('Todas las tareas están completadas (100 %). La actividad está pendiente de revisión: revisa la evidencia de cada tarea y aprueba o rechaza.') }}</p>
                            @can('reviewEvidence', $task)
                                <div class="flex flex-wrap items-center gap-3">
                                    <form action="{{ route('tasks.evidence.approve', $task) }}" method="POST">
                                        @csrf
                                        <x-ui.primary-button type="submit">{{ __('Aprobar actividad') }}</x-ui.primary-button>
                                    </form>
                                    <button type="button" x-data @click="$dispatch('open-modal', 'reject-evidence')" class="text-sm text-red-600 hover:underline font-medium">
                                        {{ __('Rechazar actividad') }}
                                    </button>
                                </div>
                            @endcan
                        </div>
                    @elseif ($task->estaAprobada())
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800">
                            {{ __('Actividad aprobada por el líder el :date.', ['date' => $task->completado_en?->format('d/m/Y H:i')]) }}
                        </div>
                    @endif

                    <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">{{ __('Tareas') }}</h3>
                            @if ($task->subtareas->isNotEmpty())
                                <span class="text-xs text-gray-400">{{ __(':done de :count completadas · :percent %', ['done' => $task->subtareas->where('estado', \App\Support\Enums\TaskStatus::Completed)->count(), 'count' => $task->subtareas->count(), 'percent' => $task->porcentaje_progreso]) }}</span>
                            @endif
                        </div>

                        @if ($task->subtareas->isNotEmpty())
                            <p class="text-xs text-gray-500">{{ __('Cada tarea vale :each % de la actividad.', ['each' => round(100 / $task->subtareas->count(), 1)]) }}</p>
                        @endif

                        <ul class="space-y-4">
                            @forelse ($task->subtareas as $subtarea)
                                @php $hecha = $subtarea->estado === \App\Support\Enums\TaskStatus::Completed; @endphp
                                <li id="tarea-{{ $subtarea->id }}" class="border border-gray-100 rounded-lg p-4 text-sm space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900">{{ $subtarea->titulo }}</p>
                                            @if ($subtarea->descripcion)
                                                <p class="text-gray-600 mt-1">{{ $subtarea->descripcion }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <x-ui.badge :color="$hecha ? 'green' : 'gray'">{{ $hecha ? __('Completada') : __('Pendiente') }}</x-ui.badge>
                                            @can('manageActivityTasks', $task)
                                                @if ($task->subtareas->count() > 1)
                                                    <form action="{{ route('tasks.destroy', $subtarea) }}" method="POST" onsubmit="return confirm('{{ __('¿Quitar esta tarea?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs text-gray-400 hover:text-red-600">{{ __('Quitar') }}</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </div>

                                    @if ($subtarea->evidencias->isNotEmpty())
                                        <ul class="divide-y divide-gray-100 border border-gray-100 rounded-lg">
                                            @foreach ($subtarea->evidencias as $evidencia)
                                                <li class="p-3 flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $evidencia->esArchivo() ? __('Archivo / imagen') : __('Enlace') }}</span>
                                                        @if ($evidencia->esArchivo())
                                                            <a href="{{ route('tasks.evidence.download', [$subtarea, $evidencia]) }}" class="text-emerald-600 hover:underline font-medium truncate block">{{ $evidencia->nombre_archivo }}</a>
                                                        @else
                                                            <a href="{{ $evidencia->url }}" target="_blank" rel="noopener" class="text-emerald-600 hover:underline font-medium truncate block">{{ $evidencia->url }}</a>
                                                        @endif
                                                        @if ($evidencia->comentario)
                                                            <p class="text-gray-600 mt-1">{{ $evidencia->comentario }}</p>
                                                        @endif
                                                    </div>
                                                    <span class="text-xs text-gray-400 shrink-0 text-right">
                                                        {{ $evidencia->user?->name ?? __('Sistema') }}<br>
                                                        {{ $evidencia->created_at->format('d/m/Y H:i') }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($subtarea->puedeRecibirEvidencia())
                                        @can('submitEvidence', $subtarea)
                                            <form action="{{ route('tasks.evidence.store', $subtarea) }}" method="POST" enctype="multipart/form-data"
                                                  class="space-y-3 pt-3 border-t border-gray-100" x-data="{ links: [''] }">
                                                @csrf
                                                <div>
                                                    <x-forms.input-label :value="__('Archivos o imágenes')" />
                                                    <input name="archivos[]" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                                                           class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-emerald-50 file:text-emerald-700 file:text-sm file:font-medium hover:file:bg-emerald-100">
                                                </div>
                                                <div>
                                                    <x-forms.input-label :value="__('Enlaces')" />
                                                    <template x-for="(link, index) in links" :key="index">
                                                        <div class="flex gap-2 mt-1">
                                                            <input type="url" :name="'enlaces[' + index + ']'" x-model="links[index]" placeholder="https://drive.google.com/..."
                                                                   class="block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                                                            <button type="button" @click="links.splice(index, 1)" x-show="links.length > 1" class="text-gray-400 hover:text-red-600 shrink-0 px-2">{{ __('Quitar') }}</button>
                                                        </div>
                                                    </template>
                                                    <button type="button" @click="links.push('')" class="text-xs text-emerald-600 hover:underline mt-2">{{ __('+ Agregar otro enlace') }}</button>
                                                </div>
                                                <div>
                                                    <x-forms.input-label :value="__('Comentario (opcional)')" />
                                                    <textarea name="comentario" rows="2" class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm"></textarea>
                                                </div>
                                                @if ((int) old('_evidence_task') === $subtarea->id)
                                                    <x-forms.input-error :messages="array_merge($errors->get('archivos'), $errors->get('archivos.*'), $errors->get('enlaces.*'))" />
                                                @endif
                                                <input type="hidden" name="_evidence_task" value="{{ $subtarea->id }}">
                                                <x-ui.primary-button type="submit">{{ __('Completar tarea con evidencia') }}</x-ui.primary-button>
                                            </form>
                                        @endcan
                                    @endif
                                </li>
                            @empty
                                <li class="py-2 text-sm text-gray-500">{{ __('Esta actividad todavía no tiene tareas.') }}</li>
                            @endforelse
                        </ul>

                        @can('manageActivityTasks', $task)
                            <form action="{{ route('tasks.children.store', $task) }}" method="POST" class="space-y-3 pt-4 border-t border-gray-100">
                                @csrf
                                <x-forms.input-label for="sub_titulo" :value="__('Agregar tarea')" />
                                <x-forms.text-input id="sub_titulo" name="titulo" type="text" class="block w-full" required
                                    placeholder="{{ __('Título de la tarea') }}" />
                                <x-forms.input-error :messages="$errors->get('titulo')" class="mt-1" />
                                <textarea name="descripcion" rows="2" placeholder="{{ __('Descripción de lo que debe hacer el trabajador') }}"
                                    class="block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm"></textarea>
                                <x-ui.primary-button type="submit">{{ __('Agregar tarea') }}</x-ui.primary-button>
                            </form>
                        @endcan
                    </div>

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
        <x-ui.form-modal name="edit-task" :title="$task->esActividad() ? __('Editar actividad') : __('Editar tarea')" :subtitle="$task->project?->nombre ?? __('Proyecto archivado')"
            :action="route('tasks.update', $task)" method="PUT" max-width="3xl">
            @include('tasks.html.partials.form')
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
                    class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ old('motivo') }}</textarea>
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
</x-app-layout>
