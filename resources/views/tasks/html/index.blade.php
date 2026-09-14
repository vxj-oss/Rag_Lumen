<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tareas') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('Arrastra un ticket a un estado para cambiarlo · clic para expandir') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tasks.export') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    {{ __('Exportar CSV') }}
                </a>
                @can('create', \App\Models\Task::class)
                    <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-task')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Nueva tarea') }}
                    </x-ui.primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="ticketBoard()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.auth-session-status :status="session('status')" class="px-1" />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui.stat-card :label="__('Tareas totales')" :value="$totalCount" color="emerald"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' />
                <x-ui.stat-card :label="__('Pendientes')" :value="$pendingCount" color="gray"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />' />
                <x-ui.stat-card :label="__('Bloqueadas')" :value="$blockedCount" color="red"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />' />
                <x-ui.stat-card :label="__('Atrasadas')" :value="$overdueCount" color="yellow"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />' />
            </div>

            <x-ui.card padding="p-4">
                <form method="GET" action="{{ route('tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Buscar') }}</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Título...') }}"
                               class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Proyecto') }}</label>
                        <select name="project_id" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ (string) ($filters['project_id'] ?? '') === (string) $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Estado') }}</label>
                        <select name="status" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Prioridad') }}</label>
                        <select name="priority" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" {{ ($filters['priority'] ?? '') === $priority->value ? 'selected' : '' }}>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">{{ __('Filtrar') }}</button>
                        @if (array_filter($filters ?? []))
                            <a href="{{ route('tasks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Limpiar') }}</a>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            {{-- Zonas de soltado por estado --}}
            <div class="flex flex-wrap gap-2" role="toolbar" aria-label="{{ __('Cambiar estado arrastrando') }}">
                <span class="text-xs font-medium text-gray-500 self-center mr-1">{{ __('Arrastrar a:') }}</span>
                @foreach ($statuses as $status)
                    <div data-drop-status="{{ $status->value }}"
                         @dragover.prevent="$el.classList.add('ring-2','ring-emerald-500','bg-emerald-50')"
                         @dragleave="$el.classList.remove('ring-2','ring-emerald-500','bg-emerald-50')"
                         @drop.prevent="$el.classList.remove('ring-2','ring-emerald-500','bg-emerald-50'); dropOnStatus($event, '{{ $status->value }}')"
                         class="px-3 py-1.5 rounded-full text-xs font-semibold bg-white ring-1 ring-gray-300 text-gray-600 transition select-none"
                         :class="draggingId ? 'ring-emerald-400 border-dashed cursor-copy' : ''">
                        {{ $status->label() }}
                    </div>
                @endforeach
                <span x-show="draggingId" class="text-xs text-emerald-600 self-center" x-cloak>{{ __('Suelta sobre un estado…') }}</span>
            </div>
            <p x-show="toast" x-text="toast" x-cloak class="text-sm rounded-lg px-3 py-2"
               :class="toastOk ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-red-50 text-red-700 ring-1 ring-red-200'"></p>

            {{-- Grid de tickets --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse ($tasks as $task)
                    @php $canUpdate = auth()->user()->can('update', $task); @endphp
                    <article id="ticket-{{ $task->id }}"
                        data-task-id="{{ $task->id }}"
                        data-status="{{ $task->status->value }}"
                        draggable="{{ $canUpdate ? 'true' : 'false' }}"
                        @dragstart="dragStart($event, {{ $task->id }})"
                        @dragend="draggingId = null"
                        @click.self="toggle({{ $task->id }})"
                        class="ticket-card group relative bg-white rounded-2xl shadow-sm ring-1 ring-gray-200 overflow-hidden hover:shadow-md hover:ring-emerald-300 transition {{ $canUpdate ? 'cursor-grab active:cursor-grabbing' : '' }}">

                        {{-- Cinta lateral por prioridad --}}
                        <span class="absolute inset-y-0 left-0 w-1.5
                            @if($task->priority->value === 'critical') bg-red-500
                            @elseif($task->priority->value === 'high') bg-orange-500
                            @elseif($task->priority->value === 'medium') bg-amber-400
                            @else bg-gray-300 @endif"></span>

                        <div class="pl-4 pr-3 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1 cursor-pointer" @click="toggle({{ $task->id }})">
                                    <p class="text-[11px] font-mono text-gray-400">#{{ $task->id }} · {{ $task->project->name }}</p>
                                    <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2">{{ $task->title }}</h3>
                                </div>
                                <button @click="toggle({{ $task->id }})" class="p-1 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition shrink-0" :title="expanded === {{ $task->id }} ? '{{ __('Contraer') }}' : '{{ __('Expandir') }}'">
                                    <svg class="w-4 h-4 transition-transform" :class="expanded === {{ $task->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                <span data-role="status-badge"><x-ui.badge :color="$task->status->color()">{{ $task->status->label() }}</x-ui.badge></span>
                                <x-ui.badge :color="$task->priority->color()">{{ $task->priority->label() }}</x-ui.badge>
                                @if ($task->isOverdue())
                                    <x-ui.badge color="red">{{ __('Atrasada') }}</x-ui.badge>
                                @endif
                            </div>

                            <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1.5 min-w-0">
                                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 inline-flex items-center justify-center font-bold text-[11px] shrink-0">
                                        {{ strtoupper(mb_substr($task->assignee?->first_name ?? '–', 0, 1)) }}
                                    </span>
                                    <span class="truncate">{{ $task->assignee?->fullName() ?? __('Sin asignar') }}</span>
                                </span>
                                <span class="{{ $task->isOverdue() ? 'text-red-600 font-semibold' : '' }}">📅 {{ $task->due_date?->format('d/m/Y') ?? '—' }}</span>
                            </div>

                            <div class="mt-2"><x-ui.progress-bar :value="$task->progress_percentage" /></div>

                            {{-- Detalle expandible --}}
                            <div x-show="expanded === {{ $task->id }}" x-collapse x-cloak class="mt-3 pt-3 border-t border-dashed border-gray-200 space-y-2">
                                @if ($task->description)
                                    <p class="text-xs text-gray-600 leading-relaxed">{{ \Illuminate\Support\Str::limit($task->description, 280) }}</p>
                                @endif
                                @if ($task->status->value === 'blocked' && $task->blocked_reason)
                                    <p class="text-xs bg-red-50 ring-1 ring-red-200 text-red-700 rounded-lg px-2 py-1.5">⛔ {{ \Illuminate\Support\Str::limit($task->blocked_reason, 200) }}</p>
                                @endif
                                <p class="text-[11px] text-gray-400">⏱ {{ $task->estimated_hours ?? '—' }}h est. / {{ $task->actual_hours ?? '—' }}h reales</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-xs font-medium">
                                    <a href="{{ route('tasks.show', $task) }}" class="text-emerald-600 hover:text-emerald-800">{{ __('Abrir ticket →') }}</a>
                                    @can('update', $task)
                                        <button x-data @click="$dispatch('open-modal', 'edit-task-{{ $task->id }}')" class="text-gray-600 hover:text-emerald-700">{{ __('Editar') }}</button>
                                    @endcan
                                    @can('delete', $task)
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="inline" @submit.prevent="if (confirm(@js(__('¿Eliminar esta tarea?')))) $el.submit()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Eliminar') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        {{-- Overlay mientras se guarda --}}
                        <div data-role="saving" class="hidden absolute inset-0 bg-white/70 items-center justify-center text-xs font-medium text-emerald-700">Guardando…</div>
                    </article>

                    @can('update', $task)
                        <x-ui.form-modal :name="'edit-task-'.$task->id" :title="__('Editar tarea')"
                            :subtitle="$task->project->name" :action="route('tasks.update', $task)" method="PUT" max-width="3xl">
                            <x-slot name="hidden"><input type="hidden" name="_edit_id" value="{{ $task->id }}"></x-slot>
                            @include('tasks.html.partials.form')
                        </x-ui.form-modal>
                    @endcan
                @empty
                    <div class="col-span-full bg-white rounded-2xl ring-1 ring-gray-200 px-6 py-16 text-center">
                        <p class="text-4xl">🎫</p>
                        <p class="mt-2 text-sm text-gray-500">{{ __('Todavía no hay tareas registradas.') }}</p>
                    </div>
                @endforelse
            </div>

            {{ $tasks->links() }}
        </div>
    </div>

    <script>
        function ticketBoard() {
            return {
                expanded: null,
                draggingId: null,
                toast: '',
                toastOk: true,
                toggle(id) { this.expanded = this.expanded === id ? null : id; },
                dragStart(event, id) {
                    this.draggingId = id;
                    event.dataTransfer.setData('text/plain', String(id));
                    event.dataTransfer.effectAllowed = 'move';
                },
                async dropOnStatus(event, status) {
                    const id = event.dataTransfer.getData('text/plain') || this.draggingId;
                    this.draggingId = null;
                    if (!id) return;
                    const card = document.getElementById('ticket-' + id);
                    if (card && card.dataset.status === status) return;
                    const overlay = card?.querySelector('[data-role="saving"]');
                    if (overlay) { overlay.classList.remove('hidden'); overlay.classList.add('flex'); }
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content;
                        const res = await fetch(@js(route('tasks.index')) + '/' + id + '/status', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ status }),
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        if (card) card.dataset.status = data.status;
                        const badge = card?.querySelector('[data-role="status-badge"]');
                        if (badge) badge.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-' + data.status_color + '-100 text-' + data.status_color + '-800">' + data.status_label + '</span>';
                        this.toastOk = true;
                        this.toast = 'Ticket #' + id + ' → ' + data.status_label;
                    } catch (e) {
                        this.toastOk = false;
                        this.toast = 'No se pudo cambiar el estado. Recarga e intenta de nuevo.';
                    } finally {
                        if (overlay) { overlay.classList.add('hidden'); overlay.classList.remove('flex'); }
                        setTimeout(() => this.toast = '', 3500);
                    }
                },
            };
        }

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
                    const response = await fetch(@js(route('tasks.live-status')), { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    if (lastSignature === null) { lastSignature = data.signature; return; }
                    if (data.signature !== lastSignature) window.location.reload();
                } catch (e) {}
            }
            setInterval(poll, 7000);
        })();
    </script>

    @can('create', \App\Models\Task::class)
        <x-ui.form-modal name="create-task" :title="__('Nueva tarea')" :subtitle="__('Registra una nueva tarea en un proyecto')"
            :action="route('tasks.store')" max-width="3xl" submit-label="{{ __('Crear tarea') }}">
            <x-slot name="hidden"><input type="hidden" name="_form" value="create"></x-slot>
            @php $task = null; @endphp
            @include('tasks.html.partials.form')
        </x-ui.form-modal>
    @endcan

    @if ($errors->any())
        @php $modalToOpen = old('_edit_id') ? 'edit-task-'.old('_edit_id') : (old('_form') === 'create' ? 'create-task' : null); @endphp
        @if ($modalToOpen)
            <div x-data x-init="$dispatch('open-modal', @js($modalToOpen))"></div>
        @endif
    @endif
</x-app-layout>
