@php
    $task = $task ?? null;
    $canManageStatus = ! auth()->user()->isPlainEmployee();
@endphp

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2">{{ __('Información general') }}</h4>

@unless ($task)
    <div>
        <x-forms.input-label for="proyecto_id" :value="__('Proyecto')" />
        <x-forms.select id="proyecto_id" name="proyecto_id" class="mt-1 block w-full"
            :options="$projects->mapWithKeys(fn ($p) => [$p->id => $p->nombre])"
            :selected="old('proyecto_id', request('project_id'))" required />
        <x-forms.input-error :messages="$errors->get('proyecto_id')" class="mt-2" />
    </div>
@else
    <div>
        <x-forms.input-label :value="__('Proyecto')" />
        <p class="mt-1 text-sm text-gray-700">{{ $task->project?->nombre ?? __('Proyecto archivado') }}</p>
    </div>
@endunless

<div>
    <x-forms.input-label for="titulo" :value="__('Título')" />
    <x-forms.text-input id="titulo" name="titulo" type="text" class="mt-1 block w-full"
        :value="old('titulo', $task?->titulo)" required autofocus />
    <x-forms.input-error :messages="$errors->get('titulo')" class="mt-2" />
</div>

<div>
    <x-forms.input-label for="descripcion" :value="__('Descripción')" />
    <textarea id="descripcion" name="descripcion" rows="2"
        class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ old('descripcion', $task?->descripcion) }}</textarea>
    <x-forms.input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Asignación y estado') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <x-forms.input-label for="asignado_a" :value="__('Asignado a')" />
        <x-forms.select id="asignado_a" name="asignado_a" class="mt-1 block w-full"
            :options="['' => __('— Sin asignar —')] + $employees->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()"
            :selected="old('asignado_a', $task?->asignado_a)" />
        <x-forms.input-error :messages="$errors->get('asignado_a')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label :value="__('Estado')" />
        @if ($canManageStatus)
            <x-forms.select id="estado_id" name="estado_id" class="mt-1 block w-full"
                :options="$projectStates->mapWithKeys(fn ($s) => [$s->id => $s->nombre])"
                :selected="old('estado_id', $task?->estado_id)" />
            <x-forms.input-error :messages="$errors->get('estado_id')" class="mt-2" />
        @else
            <div class="mt-1">
                <x-ui.badge :color="$task?->state?->color ?? 'gray'">{{ $task?->state?->nombre ?? '—' }}</x-ui.badge>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ __('El estado avanza automáticamente según el avance registrado.') }}</p>
        @endif
    </div>
    <div>
        <x-forms.input-label for="prioridad" :value="__('Prioridad')" />
        <x-forms.select id="prioridad" name="prioridad" class="mt-1 block w-full"
            :options="collect($priorities)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="old('prioridad', $task?->prioridad?->value)" />
        <x-forms.input-error :messages="$errors->get('prioridad')" class="mt-2" />
    </div>
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Fechas y horas') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <x-forms.input-label for="fecha_inicio" :value="__('Fecha de inicio')" />
        <x-forms.text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full"
            :value="old('fecha_inicio', $task?->fecha_inicio?->format('Y-m-d'))" />
        <x-forms.input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label for="fecha_vencimiento" :value="__('Fecha límite')" />
        <x-forms.text-input id="fecha_vencimiento" name="fecha_vencimiento" type="date" class="mt-1 block w-full"
            :value="old('fecha_vencimiento', $task?->fecha_vencimiento?->format('Y-m-d'))" />
        <x-forms.input-error :messages="$errors->get('fecha_vencimiento')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label for="horas_estimadas" :value="__('Horas estimadas')" />
        <x-forms.text-input id="horas_estimadas" name="horas_estimadas" type="number" step="0.01" min="0" class="mt-1 block w-full"
            :value="old('horas_estimadas', $task?->horas_estimadas)" />
        <x-forms.input-error :messages="$errors->get('horas_estimadas')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label for="horas_reales" :value="__('Horas reales')" />
        <x-forms.text-input id="horas_reales" name="horas_reales" type="number" step="0.01" min="0" class="mt-1 block w-full"
            :value="old('horas_reales', $task?->horas_reales)" />
        <x-forms.input-error :messages="$errors->get('horas_reales')" class="mt-2" />
    </div>
</div>

@isset($task)
    <div class="flex items-center gap-2 bg-emerald-50 rounded-lg px-3 py-2">
        <x-ui.progress-bar :value="$task->porcentaje_progreso" />
        <p class="text-xs text-emerald-700">{{ __('El avance se actualiza desde "Registrar avance" en el detalle de la tarea.') }}</p>
    </div>
@endisset

@if ($canManageStatus)
    <div>
        <x-forms.input-label for="motivo_bloqueo" :value="__('Motivo de bloqueo (si aplica)')" />
        <textarea id="motivo_bloqueo" name="motivo_bloqueo" rows="2"
            class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ old('motivo_bloqueo', $task?->motivo_bloqueo) }}</textarea>
        <x-forms.input-error :messages="$errors->get('motivo_bloqueo')" class="mt-2" />
    </div>
@elseif ($task?->motivo_bloqueo)
    <div>
        <x-forms.input-label :value="__('Motivo de bloqueo')" />
        <p class="mt-1 text-sm text-gray-700">{{ $task->motivo_bloqueo }}</p>
    </div>
@endif
