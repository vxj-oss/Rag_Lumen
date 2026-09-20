@php
    $task = $task ?? null;
    $canManageStatus = ! auth()->user()->isPlainEmployee();
@endphp

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2">{{ __('Información general') }}</h4>

@unless ($task)
    <div>
        <x-forms.input-label for="project_id" :value="__('Proyecto')" />
        <x-forms.select id="project_id" name="project_id" class="mt-1 block w-full"
            :options="$projects->mapWithKeys(fn ($p) => [$p->id => $p->name])"
            :selected="old('project_id', request('project_id'))" required />
        <x-forms.input-error :messages="$errors->get('project_id')" class="mt-2" />
    </div>
@else
    <div>
        <x-forms.input-label :value="__('Proyecto')" />
        <p class="mt-1 text-sm text-gray-700">{{ $task->project?->name ?? __('Proyecto archivado') }}</p>
    </div>
@endunless

<div>
    <x-forms.input-label for="title" :value="__('Título')" />
    <x-forms.text-input id="title" name="title" type="text" class="mt-1 block w-full"
        :value="old('title', $task?->title)" required autofocus />
    <x-forms.input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div>
    <x-forms.input-label for="description" :value="__('Descripción')" />
    <textarea id="description" name="description" rows="2"
        class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ old('description', $task?->description) }}</textarea>
    <x-forms.input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Asignación y estado') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <x-forms.input-label for="assigned_to" :value="__('Asignado a')" />
        <x-forms.select id="assigned_to" name="assigned_to" class="mt-1 block w-full"
            :options="['' => __('— Sin asignar —')] + $employees->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()"
            :selected="old('assigned_to', $task?->assigned_to)" />
        <x-forms.input-error :messages="$errors->get('assigned_to')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label :value="__('Estado')" />
        @if ($canManageStatus)
            <x-forms.select id="status_id" name="status_id" class="mt-1 block w-full"
                :options="$projectStates->mapWithKeys(fn ($s) => [$s->id => $s->name])"
                :selected="old('status_id', $task?->status_id)" />
            <x-forms.input-error :messages="$errors->get('status_id')" class="mt-2" />
        @else
            <div class="mt-1">
                <x-ui.badge :color="$task?->state?->color ?? 'gray'">{{ $task?->state?->name ?? '—' }}</x-ui.badge>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ __('El estado avanza automáticamente según el avance registrado.') }}</p>
        @endif
    </div>
    <div>
        <x-forms.input-label for="priority" :value="__('Prioridad')" />
        <x-forms.select id="priority" name="priority" class="mt-1 block w-full"
            :options="collect($priorities)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="old('priority', $task?->priority?->value)" />
        <x-forms.input-error :messages="$errors->get('priority')" class="mt-2" />
    </div>
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Fechas y horas') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-forms.input-label for="start_date" :value="__('Fecha de inicio')" />
        <x-forms.text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
            :value="old('start_date', $task?->start_date?->format('Y-m-d'))" />
        <x-forms.input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label for="due_date" :value="__('Fecha límite')" />
        <x-forms.text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full"
            :value="old('due_date', $task?->due_date?->format('Y-m-d'))" />
        <x-forms.input-error :messages="$errors->get('due_date')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label for="estimated_hours" :value="__('Horas estimadas')" />
        <x-forms.text-input id="estimated_hours" name="estimated_hours" type="number" step="0.01" min="0" class="mt-1 block w-full"
            :value="old('estimated_hours', $task?->estimated_hours)" />
        <x-forms.input-error :messages="$errors->get('estimated_hours')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label for="actual_hours" :value="__('Horas reales')" />
        <x-forms.text-input id="actual_hours" name="actual_hours" type="number" step="0.01" min="0" class="mt-1 block w-full"
            :value="old('actual_hours', $task?->actual_hours)" />
        <x-forms.input-error :messages="$errors->get('actual_hours')" class="mt-2" />
    </div>
</div>

@isset($task)
    <div class="flex items-center gap-2 bg-emerald-50 rounded-lg px-3 py-2">
        <x-ui.progress-bar :value="$task->progress_percentage" />
        <p class="text-xs text-emerald-700">{{ __('El avance se actualiza desde "Registrar avance" en el detalle de la tarea.') }}</p>
    </div>
@endisset

@if ($canManageStatus)
    <div>
        <x-forms.input-label for="blocked_reason" :value="__('Motivo de bloqueo (si aplica)')" />
        <textarea id="blocked_reason" name="blocked_reason" rows="2"
            class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ old('blocked_reason', $task?->blocked_reason) }}</textarea>
        <x-forms.input-error :messages="$errors->get('blocked_reason')" class="mt-2" />
    </div>
@elseif ($task?->blocked_reason)
    <div>
        <x-forms.input-label :value="__('Motivo de bloqueo')" />
        <p class="mt-1 text-sm text-gray-700">{{ $task->blocked_reason }}</p>
    </div>
@endif
