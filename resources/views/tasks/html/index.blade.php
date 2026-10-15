<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Tablero de actividades')" :subtitle="__('Arrastra una tarjeta a una columna para cambiar su estado')" :eyebrow="__('Vista operativa')">
            @can('create', \App\Models\Task::class)
                <x-slot name="actions">
                    <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-task')" class="min-h-[44px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Nueva actividad') }}
                    </x-ui.primary-button>
                </x-slot>
            @endcan
        </x-ui.page-header>
    </x-slot>

    <div class="py-4" x-data="kanbanBoard()" x-init="init()">
        <div class="max-w-[1500px] mx-auto sm:px-6 lg:px-8 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="relative bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-3 flex items-center gap-3 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-400 to-blue-600"></div>
                    <div class="min-w-0 relative">
                        <p class="text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.open ?? '…'">…</p>
                        <p class="text-xs text-slate-500">{{ __('Actividades abiertas') }}</p>
                    </div>
                </div>
                <div class="relative bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-3 flex items-center gap-3 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-yellow-400 to-yellow-500"></div>
                    <div class="min-w-0 relative">
                        <p class="text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.overdue ?? '…'">…</p>
                        <p class="text-xs text-slate-500">{{ __('Actividades atrasadas') }}</p>
                    </div>
                </div>
                <div class="relative bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-3 flex items-center gap-3 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
                    <div class="min-w-0 relative">
                        <p class="text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.completed_week ?? '…'">…</p>
                        <p class="text-xs text-slate-500">{{ __('Completadas esta semana') }}</p>
                    </div>
                </div>
            </div>

            <x-ui.card padding="p-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Proyecto') }}</label>
                        <select x-model="filters.project_id" @change="onProjectChange()"
                            class="w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->codigo }} · {{ $project->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Empleado') }}</label>
                        <select x-model="filters.assigned_to" @change="fetchBoard()"
                            class="w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            <template x-for="emp in employees" :key="emp.id">
                                <option :value="emp.id" x-text="emp.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Área') }}</label>
                        <select x-model="filters.area_id" @change="fetchBoard()"
                            class="w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            <template x-for="area in areas" :key="area.id">
                                <option :value="area.id" x-text="area.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Prioridad') }}</label>
                        <select x-model="filters.priority" @change="fetchBoard()"
                            class="w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Buscar') }}</label>
                        <input type="text" x-model="filters.search" @input.debounce.400ms="fetchBoard()" placeholder="{{ __('Título...') }}"
                               class="w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                    </div>
                    <div class="flex items-center gap-3 pb-2">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                            <input type="checkbox" x-model="filters.mine" @change="fetchBoard()"
                                   class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                            {{ __('Solo mis actividades') }}
                        </label>
                    </div>
                </div>
            </x-ui.card>

            <p x-show="toast" x-text="toast" x-cloak class="text-sm rounded-xl px-3 py-2"
               :class="toastOk ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-red-50 text-red-700 ring-1 ring-red-200'"></p>

            <div x-show="loading" class="text-sm text-slate-500">{{ __('Cargando tablero…') }}</div>

            <div class="pb-6">
                <div class="grid gap-3 items-start" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <template x-for="col in columns" :key="col.id || col.slug">
                        <section class="w-full min-h-[220px] bg-slate-50 border border-slate-200 border-t-4 rounded-xl flex flex-col shadow-sm"
                            :data-column-id="String(col.id)"
                            :class="accentClass(col.color) + ' ' + (hoverColumnId === String(col.id) ? 'border-emerald-500 bg-emerald-50/40 shadow-md' : '')">
                            <header class="flex items-center gap-2 px-4 py-2.5 border-b border-slate-200 bg-white rounded-t-xl shrink-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="dotClass(col.color)"></span>
                                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide truncate" x-text="col.name"></h3>
                                <span class="ml-auto text-xs font-semibold text-slate-600 bg-slate-100 border border-slate-200 rounded-full px-2 py-0.5" x-text="cardsIn(col.slug, col.id).length"></span>
                            </header>

                            <div class="p-2 gap-2 flex-1 overflow-y-auto flex flex-col">
                                <template x-for="card in pagedCardsIn(col)" :key="card.id">
                                    <article :id="'ticket-' + card.id"
                                        class="bg-white rounded-xl border border-slate-200 p-2.5 space-y-1.5 shadow-sm hover:shadow-md hover:border-emerald-400 transition"
                                        :class="draggingId === card.id ? 'opacity-60 border-emerald-500' : ''">
                                        <div class="flex items-start justify-between gap-2">
                                            <button x-show="card.can_update" type="button"
                                                @pointerdown.stop.prevent="pointerDragStart($event, card)"
                                                :aria-label="@js(__('Mover tarjeta'))"
                                                title="{{ __('Arrastrar para cambiar el estado') }}"
                                                class="-ml-1 -mt-1 p-1 rounded-xl text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 select-none touch-none"
                                                :class="draggingId === card.id ? 'cursor-grabbing' : 'cursor-grab'">
                                                <svg class="w-4 h-4 pointer-events-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <circle cx="7" cy="5" r="1.25" /><circle cx="13" cy="5" r="1.25" />
                                                    <circle cx="7" cy="10" r="1.25" /><circle cx="13" cy="10" r="1.25" />
                                                    <circle cx="7" cy="15" r="1.25" /><circle cx="13" cy="15" r="1.25" />
                                                </svg>
                                            </button>
                                            <p class="text-[11px] font-mono text-slate-500" x-text="card.code"></p>
                                            <div class="relative" x-data="{ open: false }">
                                                <button @click="open = !open" :aria-label="@js(__('Acciones de la tarjeta'))"
                                                    class="p-1 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 min-w-[32px] min-h-[32px] inline-flex items-center justify-center font-bold tracking-widest" title="···">···</button>
                                                <div x-show="open" @click.outside="open = false" @keydown.escape.window="open = false" x-cloak
                                                     class="absolute right-0 z-20 w-44 max-h-64 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-lg py-1 text-sm">
                                                    <a :href="@js(route('tasks.show', ['task' => '__ID__'])).replace('__ID__', card.id)"
                                                       class="block px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">{{ __('Abrir ticket') }}</a>
                                                    <template x-for="target in columns.filter((c) => String(c.id) !== String(card.status_id) && c.slug !== card.status_slug)" :key="target.slug">
                                                        <button x-show="card.can_update" @click="open = false; moveViaMenu(card, target)"
                                                            class="block w-full text-left px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                                                            <span x-text="@js(__('Mover a')) + ' ' + target.name"></span>
                                                        </button>
                                                    </template>
                                                    <button x-show="card.can_delete" @click="open = false; deleteCard(card)"
                                                            class="block w-full text-left px-3 py-2 text-red-600 hover:bg-red-50">{{ __('Eliminar') }}</button>
                                                </div>
                                            </div>
                                        </div>

                                        <h4 class="text-sm font-semibold text-slate-900 leading-snug break-words line-clamp-2" x-text="card.title"></h4>

                                        <div class="flex items-center gap-1.5 text-xs text-slate-500 min-w-0">
                                            <span x-show="card.assignee_initials" x-text="card.assignee_initials"
                                                  class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 inline-flex items-center justify-center font-bold text-[10px] shrink-0"></span>
                                            <span class="truncate" x-text="card.assignee_name || @js(__('Sin asignar'))"></span>
                                            <span class="ml-auto inline-flex items-center gap-1 font-medium text-slate-600 shrink-0 text-[11px]">
                                                <span class="w-2 h-2 rounded-full" :class="dotClass(card.priority_color)"></span>
                                                <span x-text="card.priority_label"></span>
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                            <span x-show="card.area" x-text="card.area"
                                                  class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium shrink-0"></span>
                                            <span class="shrink-0" x-text="card.project_code"></span>
                                            <span x-show="card.due" class="ml-auto truncate"
                                                  :class="card.overdue ? 'text-red-600 font-semibold' : ''"
                                                  x-text="card.due ? card.due.label : ''"></span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-slate-200 rounded-full h-1.5">
                                                <div class="bg-emerald-500 h-1.5 rounded-full" :style="'width: ' + card.progress + '%'"></div>
                                            </div>
                                            <span class="text-[11px] text-slate-500 shrink-0 whitespace-nowrap"
                                                  x-text="card.progress + '%'"></span>
                                        </div>

                                        <div x-show="card.blocking" class="text-[11px] font-bold text-red-700 bg-red-50 border border-red-200 rounded-xl px-2 py-1">
                                            <span x-text="@js(__('BLOQUEADA'))"></span>
                                            <span x-show="card.blocked_reason" class="block font-normal" x-text="card.blocked_reason"></span>
                                        </div>

                                        <div x-show="showSaving[card.id]" class="text-[11px] text-emerald-700">{{ __('Guardando…') }}</div>
                                    </article>
                                </template>

                                <p x-show="cardsIn(col.slug, col.id).length === 0" class="flex-1 flex items-center justify-center text-xs text-slate-400">{{ __('Sin actividades') }}</p>
                            </div>

                            <div x-show="totalPages(col) > 1" class="flex items-center justify-between gap-2 px-3 py-2 border-t border-slate-200 bg-white rounded-b-xl shrink-0">
                                <button type="button" @click="goToPage(col, currentPage(col) - 1)" :disabled="currentPage(col) === 1"
                                    class="p-1.5 rounded-xl text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 disabled:opacity-30 disabled:pointer-events-none min-w-[28px] min-h-[28px] inline-flex items-center justify-center"
                                    :aria-label="@js(__('Página anterior'))">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                </button>
                                <span class="text-xs text-slate-500 tabular-nums" x-text="currentPage(col) + ' / ' + totalPages(col)"></span>
                                <button type="button" @click="goToPage(col, currentPage(col) + 1)" :disabled="currentPage(col) >= totalPages(col)"
                                    class="p-1.5 rounded-xl text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 disabled:opacity-30 disabled:pointer-events-none min-w-[28px] min-h-[28px] inline-flex items-center justify-center"
                                    :aria-label="@js(__('Página siguiente'))">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </button>
                            </div>
                        </section>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="blockModal.open" x-cloak @keydown.escape.window="blockModal.open = false" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="blockModal.open = false"></div>
            <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-xl p-6 w-full sm:max-w-md space-y-4 max-h-[90dvh] overflow-y-auto">
                <h3 class="font-semibold text-slate-800">{{ __('Motivo de bloqueo') }}</h3>
                <p class="text-sm text-slate-500">{{ __('La tarjeta se mueve a un estado de bloqueo. Indica el motivo:') }}</p>
                <textarea x-model="blockModal.reason" rows="3"
                    class="block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm"></textarea>
                <p x-show="blockModal.error" x-text="blockModal.error" class="text-xs text-red-600"></p>
                <div class="flex justify-end gap-2">
                    <button @click="blockModal.open = false" class="px-3 py-2 rounded-xl text-sm text-slate-600 hover:bg-slate-100">{{ __('Cancelar') }}</button>
                    <button @click="confirmBlockedMove()" class="px-3 py-2 rounded-xl text-sm font-medium bg-red-600 text-white hover:bg-red-700">{{ __('Mover y bloquear') }}</button>
                </div>
            </div>
        </div>

        @can('create', \App\Models\Task::class)
            <x-ui.form-modal name="create-task" :title="__('Nueva actividad')" :subtitle="__('Define la actividad, asígnala a un trabajador y detalla sus tareas')"
                :action="route('tasks.store')" max-width="3xl" submit-label="{{ __('Crear actividad') }}">
                <div class="space-y-4" x-data="taskCreateForm()" x-init="initCreateForm()">
                    <input type="hidden" name="estado_id" x-model="estadoId">

                    <div>
                        <x-forms.input-label for="cf_project" :value="__('Proyecto')" />
                        <select id="cf_project" name="proyecto_id" x-model="projectId" @change="loadScope()" required
                            class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm">
                            <option value="">{{ __('Selecciona un proyecto…') }}</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->codigo }} · {{ $project->nombre }}</option>
                            @endforeach
                        </select>
                        <x-forms.input-error :messages="$errors->get('proyecto_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.input-label for="cf_title" :value="__('Título')" />
                        <x-forms.text-input id="cf_title" name="titulo" type="text" class="mt-1 block w-full" required />
                        <x-forms.input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.input-label for="cf_description" :value="__('Descripción (opcional)')" />
                        <textarea id="cf_description" name="descripcion" rows="2"
                            class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <x-forms.input-label for="cf_assigned" :value="__('Asignado a')" />
                            <select id="cf_assigned" name="asignado_a" required
                                class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm">
                                <option value="">{{ __('— Selecciona un trabajador —') }}</option>
                                <template x-for="e in employees" :key="e.id"><option :value="e.id" x-text="e.name"></option></template>
                            </select>
                            <x-forms.input-error :messages="$errors->get('asignado_a')" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.input-label for="cf_priority" :value="__('Prioridad')" />
                            <select id="cf_priority" name="prioridad" class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->value }}" {{ $priority === \App\Support\Enums\Priority::Medium ? 'selected' : '' }}>{{ $priority->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-forms.input-label for="cf_start" :value="__('Fecha de inicio')" />
                            <x-forms.text-input id="cf_start" name="fecha_inicio" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-forms.input-label for="cf_due" :value="__('Fecha límite')" />
                            <x-forms.text-input id="cf_due" name="fecha_vencimiento" type="date" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <x-forms.input-label :value="__('Tareas de la actividad')" />
                            <button type="button" @click="tareas.push({ titulo: '', descripcion: '' })"
                                class="text-xs font-medium text-emerald-600 hover:underline">{{ __('+ Agregar tarea') }}</button>
                        </div>
                        <p class="text-xs text-slate-400" x-text="tareas.length > 0 ? 'Cada tarea completada con evidencia suma ' + (Math.round(1000 / tareas.length) / 10) + ' % de avance.' : ''"></p>

                        <template x-for="(fila, index) in tareas" :key="index">
                            <div class="flex items-start gap-2 bg-slate-50 rounded-xl p-2">
                                <div class="flex-1 space-y-2">
                                    <input type="text" :name="'tareas[' + index + '][titulo]'" x-model="fila.titulo" required
                                        placeholder="{{ __('Título de la tarea') }}"
                                        class="block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                                    <textarea :name="'tareas[' + index + '][descripcion]'" x-model="fila.descripcion" rows="2"
                                        placeholder="{{ __('Descripción de lo que debe hacer el trabajador') }}"
                                        class="block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm"></textarea>
                                </div>
                                <button type="button" @click="tareas.splice(index, 1)" x-show="tareas.length > 1" class="text-slate-400 hover:text-red-600 shrink-0 p-2">{{ __('Quitar') }}</button>
                            </div>
                        </template>
                        <x-forms.input-error :messages="$errors->get('tareas')" class="mt-2" />
                    </div>
                </div>
            </x-ui.form-modal>
        @endcan

        <script>
            function kanbanBoard() {
                return {
                    columns: [],
                    cards: [],
                    kpis: {},
                    loading: true,
                    toast: '',
                    toastOk: true,
                    draggingId: null,
                    hoverColumnId: null,
                    dragPointerId: null,
                    dragMoved: false,
                    dragStartX: 0,
                    dragStartY: 0,
                    dragOffsetX: 0,
                    dragOffsetY: 0,
                    dragHandle: null,
                    dragGhost: null,
                    pointerMoveHandler: null,
                    pointerEndHandler: null,
                    pointerCancelHandler: null,
                    pollInterval: null,
                    boardAbortController: null,
                    boardRequestId: 0,
                    pollAbortController: null,
                    scopeAbortController: null,
                    pageHideHandler: null,
                    isNavigating: false,
                    lastSignature: undefined,
                    showSaving: {},
                    blockModal: { open: false, cardId: null, column: null, reason: '', error: '' },
                    pageSize: 1,
                    columnPage: {},
                    resizeHandler: null,
                    accentColors: {
                        gray: 'border-t-gray-400', blue: 'border-t-blue-500', indigo: 'border-t-indigo-500',
                        green: 'border-t-emerald-500', yellow: 'border-t-yellow-400', red: 'border-t-red-500',
                        orange: 'border-t-orange-500', amber: 'border-t-amber-400',
                    },
                    filters: @js([
                        'project_id' => $filters['project_id'] ?? '',
                        'assigned_to' => $filters['assigned_to'] ?? '',
                        'area_id' => $filters['area_id'] ?? '',
                        'priority' => $filters['priority'] ?? '',
                        'search' => $filters['search'] ?? '',
                        'mine' => (bool) ($filters['mine'] ?? false),
                    ]),
                    employees: @js($filterEmployees->map(fn ($e) => ['id' => $e->id, 'name' => $e->nombres . ' ' . $e->apellidos])->values()),
                    areas: @js($areas->map(fn ($a) => ['id' => $a->id, 'name' => $a->nombre])->values()),
                    dotColors: {
                        gray: 'bg-slate-400', blue: 'bg-blue-500', indigo: 'bg-indigo-500',
                        green: 'bg-emerald-500', yellow: 'bg-yellow-400', red: 'bg-red-500',
                        orange: 'bg-orange-500', amber: 'bg-amber-400',
                    },
                    init() {
                        this.pageHideHandler = () => this.destroy();
                        window.addEventListener('pagehide', this.pageHideHandler);
                        this.fetchBoard();
                        this.pollInterval = window.setInterval(() => this.poll(), 15000);
                        let resizeTimeout = null;
                        this.resizeHandler = () => {
                            clearTimeout(resizeTimeout);
                            resizeTimeout = setTimeout(() => this.syncColumnHeights(), 150);
                        };
                        window.addEventListener('resize', this.resizeHandler);
                    },
                    destroy() {
                        this.isNavigating = true;
                        if (this.pollInterval !== null) window.clearInterval(this.pollInterval);
                        this.pollInterval = null;
                        this.boardAbortController?.abort();
                        this.pollAbortController?.abort();
                        this.scopeAbortController?.abort();
                        this.cleanupPointerDrag();
                        if (this.pageHideHandler) window.removeEventListener('pagehide', this.pageHideHandler);
                        if (this.resizeHandler) window.removeEventListener('resize', this.resizeHandler);
                    },
                    syncColumnHeights() {
                        this.$nextTick(() => {
                            const sections = [...this.$el.querySelectorAll('[data-column-id]')];
                            if (!sections.length) return;
                            sections.forEach((s) => { s.style.height = 'auto'; });
                            const tallest = Math.max(...sections.map((s) => s.offsetHeight));
                            const target = Math.min(Math.max(tallest, 220), 480);
                            sections.forEach((s) => { s.style.height = target + 'px'; });
                        });
                    },
                    dotClass(color) { return this.dotColors[color] || 'bg-slate-400'; },
                    accentClass(color) { return this.accentColors[color] || 'border-t-gray-400'; },
                    cardsIn(columnSlug, columnId) {
                        return this.cards.filter((c) => {
                            if (columnId !== undefined && c.status_id !== undefined) {
                                if (String(c.status_id) === String(columnId)) return true;
                            }
                            if (c.status_slug && columnSlug) {
                                return String(c.status_slug).toLowerCase() === String(columnSlug).toLowerCase();
                            }
                            return false;
                        });
                    },
                    totalPages(col) {
                        return Math.max(1, Math.ceil(this.cardsIn(col.slug, col.id).length / this.pageSize));
                    },
                    currentPage(col) {
                        // Se autocorrige si la página guardada quedó fuera de rango
                        // (p. ej. se movió la última tarjeta de esa página a otra columna).
                        return Math.min(this.columnPage[col.slug] || 1, this.totalPages(col));
                    },
                    pagedCardsIn(col) {
                        const page = this.currentPage(col);
                        const start = (page - 1) * this.pageSize;

                        return this.cardsIn(col.slug, col.id).slice(start, start + this.pageSize);
                    },
                    goToPage(col, page) {
                        this.columnPage[col.slug] = Math.min(Math.max(1, page), this.totalPages(col));
                    },
                    boardUrl() {
                        const params = new URLSearchParams();
                        for (const [key, value] of Object.entries(this.filters)) {
                            if (value === '' || value === false || value === null || value === undefined) continue;
                            params.set(key, key === 'mine' ? '1' : value);
                        }
                        return @js(route('tasks.board')) + '?' + params.toString();
                    },
                    syncQueryString() {
                        const params = new URLSearchParams();
                        for (const [key, value] of Object.entries(this.filters)) {
                            if (value === '' || value === false || value === null) continue;
                            params.set(key, key === 'mine' ? '1' : value);
                        }
                        history.replaceState(null, '', location.pathname + (params.toString() ? '?' + params.toString() : ''));
                    },
                    async fetchBoard(silent = false) {
                        if (this.isNavigating) return;
                        const requestId = ++this.boardRequestId;
                        this.boardAbortController?.abort();
                        const controller = new AbortController();
                        this.boardAbortController = controller;
                        if (!silent) this.loading = true;
                        this.syncQueryString();
                        try {
                            const res = await fetch(this.boardUrl(), {
                                headers: { 'Accept': 'application/json' },
                                signal: controller.signal,
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            const data = await res.json();
                            if (controller.signal.aborted || requestId !== this.boardRequestId || this.isNavigating) return;
                            this.columns = data.columns;
                            this.cards = data.cards;
                            this.kpis = data.kpis;
                            this.syncColumnHeights();
                        } catch (e) {
                            if (e.name === 'AbortError' || requestId !== this.boardRequestId || this.isNavigating) return;
                            this.toastOk = false;
                            this.toast = @js(__('No se pudo cargar el tablero.'));
                        } finally {
                            if (requestId === this.boardRequestId) {
                                if (this.boardAbortController === controller) this.boardAbortController = null;
                                this.loading = false;
                            }
                        }
                    },
                    async poll() {
                        if (this.isNavigating || document.hidden || this.loading || this.boardAbortController || this.pollAbortController) return;
                        if (this.draggingId !== null || this.blockModal.open) return;
                        if (Object.values(this.showSaving).some(Boolean)) return;

                        const controller = new AbortController();
                        this.pollAbortController = controller;
                        try {
                            const res = await fetch(@js(route('tasks.live-status')), {
                                headers: { 'Accept': 'application/json' },
                                signal: controller.signal,
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            if (controller.signal.aborted || this.isNavigating) return;
                            if (this.lastSignature === undefined) { this.lastSignature = data.signature; return; }
                            if (data.signature !== this.lastSignature) {
                                this.lastSignature = data.signature;
                                await this.fetchBoard(true);
                            }
                        } catch (e) {
                            if (e.name !== 'AbortError') return;
                        } finally {
                            if (this.pollAbortController === controller) this.pollAbortController = null;
                        }
                    },
                    async onProjectChange() {
                        this.filters.assigned_to = '';
                        this.scopeAbortController?.abort();
                        const controller = new AbortController();
                        this.scopeAbortController = controller;
                        try {
                            const projectId = this.filters.project_id;
                            if (!projectId) {
                                this.employees = @js($filterEmployees->map(fn ($e) => ['id' => $e->id, 'name' => $e->nombres . ' ' . $e->apellidos])->values());
                                this.areas = @js($areas->map(fn ($a) => ['id' => $a->id, 'name' => $a->nombre])->values());
                            } else {
                                const res = await fetch(@js(route('projects.scope-data', ['project' => '__ID__'])).replace('__ID__', projectId), {
                                    headers: { 'Accept': 'application/json' },
                                    signal: controller.signal,
                                });
                                if (res.ok) {
                                    const data = await res.json();
                                    if (controller.signal.aborted || String(this.filters.project_id) !== String(projectId)) return;
                                    this.employees = data.employees;
                                    if (data.areas.length > 0) this.areas = data.areas;
                                }
                            }
                        } catch (e) {
                            if (e.name === 'AbortError') return;
                            this.toastOk = false;
                            this.toast = @js(__('No se pudieron cargar los datos del proyecto.'));
                        } finally {
                            if (this.scopeAbortController === controller) this.scopeAbortController = null;
                        }
                        this.fetchBoard();
                    },
                    pointerDragStart(event, card) {
                        if (!card.can_update || (event.pointerType === 'mouse' && event.button !== 0)) return;

                        this.draggingId = card.id;
                        this.dragPointerId = event.pointerId;
                        this.dragMoved = false;
                        this.dragStartX = event.clientX;
                        this.dragStartY = event.clientY;
                        this.dragHandle = event.currentTarget;

                        const source = event.currentTarget.closest('article');
                        if (source) {
                            const rect = source.getBoundingClientRect();
                            this.dragOffsetX = event.clientX - rect.left;
                            this.dragOffsetY = event.clientY - rect.top;
                            this.dragGhost = source.cloneNode(true);
                            this.dragGhost.removeAttribute('id');
                            this.dragGhost.setAttribute('aria-hidden', 'true');
                            [this.dragGhost, ...this.dragGhost.querySelectorAll('*')].forEach((element) => {
                                [...element.attributes].forEach((attribute) => {
                                    if (attribute.name.startsWith('x-') || attribute.name.startsWith('@') || attribute.name.startsWith(':')) {
                                        element.removeAttribute(attribute.name);
                                    }
                                });
                            });
                            Object.assign(this.dragGhost.style, {
                                position: 'fixed',
                                left: '0',
                                top: '0',
                                width: `${rect.width}px`,
                                margin: '0',
                                zIndex: '9999',
                                pointerEvents: 'none',
                                opacity: '0.9',
                                display: 'none',
                                transition: 'none',
                                willChange: 'transform',
                            });
                            document.body.appendChild(this.dragGhost);
                        }

                        document.body.style.cursor = 'grabbing';
                        this.pointerMoveHandler = (pointerEvent) => this.pointerDragMove(pointerEvent);
                        this.pointerEndHandler = (pointerEvent) => this.pointerDragEnd(pointerEvent);
                        this.pointerCancelHandler = () => this.pointerDragEnd(null, true);
                        document.addEventListener('pointermove', this.pointerMoveHandler, { passive: false });
                        document.addEventListener('pointerup', this.pointerEndHandler, true);
                        document.addEventListener('pointercancel', this.pointerCancelHandler, true);
                        window.addEventListener('blur', this.pointerCancelHandler);
                    },
                    pointerDragMove(event) {
                        if (event.pointerId !== this.dragPointerId) return;
                        if (event.pointerType === 'mouse' && event.buttons === 0) {
                            this.pointerDragEnd(event, true);
                            return;
                        }
                        event.preventDefault();

                        if (!this.dragMoved && Math.hypot(event.clientX - this.dragStartX, event.clientY - this.dragStartY) > 4) {
                            this.dragMoved = true;
                            if (this.dragGhost) this.dragGhost.style.display = 'block';
                        }
                        if (!this.dragMoved) return;

                        if (this.dragGhost) {
                            this.dragGhost.style.transform = `translate3d(${event.clientX - this.dragOffsetX}px, ${event.clientY - this.dragOffsetY}px, 0)`;
                        }
                        const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-column-id]');
                        this.hoverColumnId = target?.dataset.columnId ?? null;
                    },
                    pointerDragEnd(event, cancelled = false) {
                        if (event && event.pointerId !== this.dragPointerId) return;

                        const cardId = this.draggingId;
                        const columnId = !cancelled && this.dragMoved ? this.hoverColumnId : null;
                        const wasMoved = this.dragMoved;
                        this.cleanupPointerDrag();

                        if (!wasMoved || cancelled) return;
                        if (!columnId) {
                            this.toastOk = false;
                            this.toast = @js(__('Suelta la tarjeta sobre una columna para cambiar su estado.'));
                            setTimeout(() => this.toast = '', 3500);
                            return;
                        }

                        const column = this.columns.find((item) => String(item.id) === String(columnId));
                        if (column) this.dropOnColumn(cardId, column);
                    },
                    cleanupPointerDrag() {
                        if (this.pointerMoveHandler) document.removeEventListener('pointermove', this.pointerMoveHandler);
                        if (this.pointerEndHandler) document.removeEventListener('pointerup', this.pointerEndHandler, true);
                        if (this.pointerCancelHandler) {
                            document.removeEventListener('pointercancel', this.pointerCancelHandler, true);
                            window.removeEventListener('blur', this.pointerCancelHandler);
                        }
                        this.dragGhost?.remove();
                        document.body.style.cursor = '';
                        this.draggingId = null;
                        this.hoverColumnId = null;
                        this.dragPointerId = null;
                        this.dragMoved = false;
                        this.dragStartX = 0;
                        this.dragStartY = 0;
                        this.dragOffsetX = 0;
                        this.dragOffsetY = 0;
                        this.dragHandle = null;
                        this.dragGhost = null;
                        this.pointerMoveHandler = null;
                        this.pointerEndHandler = null;
                        this.pointerCancelHandler = null;
                    },
                    dropOnColumn(cardId, column) {
                        const card = this.cards.find((item) => String(item.id) === String(cardId));
                        if (!card || String(card.status_id) === String(column.id) || card.status_slug === column.slug) return;
                        if (!card.can_update) {
                            this.toastOk = false;
                            this.toast = @js(__('No tienes permiso para mover esta tarjeta.'));
                            setTimeout(() => this.toast = '', 3500);
                            return;
                        }
                        if (column.is_blocking && !card.blocked_reason) {
                            this.blockModal = { open: true, cardId: card.id, column, reason: '', error: '' };
                            return;
                        }
                        this.moveCard(card, column, null);
                    },
                    confirmBlockedMove() {
                        if (!this.blockModal.reason.trim()) {
                            this.blockModal.error = @js(__('Indica el motivo del bloqueo.'));
                            return;
                        }
                        const card = this.cards.find((c) => c.id === this.blockModal.cardId);
                        this.blockModal.open = false;
                        if (card) this.moveCard(card, this.blockModal.column, this.blockModal.reason.trim());
                    },
                    moveViaMenu(card, column) {
                        this.dropOnColumn(card.id, column);
                    },
                    async moveCard(card, column, blockedReason) {
                        this.showSaving[card.id] = true;
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.content;
                            const body = { status_slug: column.slug, status_id: column.id };
                            if (blockedReason) body.blocked_reason = blockedReason;
                            const res = await fetch(@js(route('tasks.status.update', ['task' => '__ID__'])).replace('__ID__', card.id), {
                                method: 'PATCH',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify(body),
                            });
                            if (res.status === 422) {
                                const data = await res.json();
                                throw new Error(data.message || @js(__('Movimiento no permitido.')));
                            }
                            if (res.status === 403) throw new Error(@js(__('No tienes permiso para ese estado.')));
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            const data = await res.json();
                            card.status_id = data.status_id;
                            card.status_slug = data.status_slug;
                            card.blocking = data.is_blocking;
                            if (blockedReason) card.blocked_reason = blockedReason;
                            card.progress = data.progress_percentage;
                            this.columns.forEach((c) => {
                                c.count = this.cards.filter((x) => String(x.status_id) === String(c.id) || x.status_slug === c.slug).length;
                            });
                            this.toastOk = true;
                            this.toast = card.code + ' → ' + data.status_label;
                            this.lastSignature = undefined;
                            this.syncColumnHeights();
                        } catch (e) {
                            this.toastOk = false;
                            this.toast = e.message || @js(__('No se pudo cambiar el estado.'));
                        } finally {
                            this.showSaving[card.id] = false;
                            setTimeout(() => this.toast = '', 3500);
                        }
                    },
                    async deleteCard(card) {
                        if (!confirm(@js(__('¿Eliminar esta actividad?')))) return;
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.content;
                            const res = await fetch(@js(route('tasks.destroy', ['task' => '__ID__'])).replace('__ID__', card.id), {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                                body: new URLSearchParams({ _method: 'DELETE' }),
                            });
                            if (res.status === 403) throw new Error(@js(__('No tienes permiso para eliminar.')));
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            this.cards = this.cards.filter((c) => c.id !== card.id);
                            this.columns.forEach((c) => {
                                c.count = this.cards.filter((x) => String(x.status_id) === String(c.id) || x.status_slug === c.slug).length;
                            });
                            this.syncColumnHeights();
                        } catch (e) {
                            this.toastOk = false;
                            this.toast = e.message;
                            setTimeout(() => this.toast = '', 3500);
                        }
                    },
                };
            }

            function taskCreateForm() {
                return {
                    projectId: '',
                    estadoId: '',
                    employees: [],
                    states: [],
                    tareas: [{ titulo: '', descripcion: '' }],
                    async initCreateForm() {},
                    async loadScope() {
                        this.estadoId = '';
                        this.employees = [];
                        this.states = [];
                        if (!this.projectId) return;
                        try {
                            const res = await fetch(@js(route('projects.scope-data', ['project' => '__ID__'])).replace('__ID__', this.projectId), { headers: { 'Accept': 'application/json' } });
                            if (!res.ok) return;
                            const data = await res.json();
                            this.employees = data.employees;
                            this.states = data.states;
                            this.estadoId = this.initialStates()[0]?.id ?? '';
                        } catch (e) {}
                    },
                    initialStates() {
                        const initials = this.states.filter((s) => s.is_initial);
                        return initials.length > 0 ? initials : this.states;
                    },
                };
            }
        </script>

        @if ($errors->any())
            <div x-data x-init="$dispatch('open-modal', 'create-task')"></div>
        @endif
    </div>
</x-app-layout>
