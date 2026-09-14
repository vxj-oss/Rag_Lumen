@php
    $project = $project ?? null;
    $isOldForThisForm = $project
        ? old('_edit_id') == $project->id
        : old('_form') === 'create';
    $old = fn (string $field, $default = null) => $isOldForThisForm ? old($field, $default) : $default;
@endphp

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2">{{ __('Información general') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-forms.input-label for="code" :value="__('Código')" />
        <x-forms.text-input id="code" name="code" type="text" class="mt-1 block w-full"
            :value="$old('code', $project?->code)" required autofocus />
        <x-forms.input-error :messages="$errors->get('code')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="name" :value="__('Nombre')" />
        <x-forms.text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="$old('name', $project?->name)" required />
        <x-forms.input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
</div>

<div>
    <x-forms.input-label for="description" :value="__('Descripción')" />
    <textarea id="description" name="description" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ $old('description', $project?->description) }}</textarea>
    <x-forms.input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-forms.input-label for="type" :value="__('Tipo de proyecto')" />
        <x-forms.select id="type" name="type" class="mt-1 block w-full"
            :options="collect($types)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="$old('type', $project?->type?->value)" />
        <x-forms.input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="client" :value="__('Cliente')" />
        <x-forms.text-input id="client" name="client" type="text" class="mt-1 block w-full"
            :value="$old('client', $project?->client)" />
        <x-forms.input-error :messages="$errors->get('client')" class="mt-2" />
    </div>
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Planificación') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <x-forms.input-label for="start_date" :value="__('Fecha de inicio')" />
        <x-forms.text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
            :value="$old('start_date', $project?->start_date?->format('Y-m-d'))" required />
        <x-forms.input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="estimated_end_date" :value="__('Fecha fin estimada')" />
        <x-forms.text-input id="estimated_end_date" name="estimated_end_date" type="date" class="mt-1 block w-full"
            :value="$old('estimated_end_date', $project?->estimated_end_date?->format('Y-m-d'))" required />
        <x-forms.input-error :messages="$errors->get('estimated_end_date')" class="mt-2" />
    </div>

    @isset($project)
        <div>
            <x-forms.input-label for="actual_end_date" :value="__('Fecha fin real')" />
            <x-forms.text-input id="actual_end_date" name="actual_end_date" type="date" class="mt-1 block w-full"
                :value="$old('actual_end_date', $project?->actual_end_date?->format('Y-m-d'))" />
            <x-forms.input-error :messages="$errors->get('actual_end_date')" class="mt-2" />
        </div>
    @endisset
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Seguimiento y asignación') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <x-forms.input-label for="status" :value="__('Estado')" />
        <x-forms.select id="status" name="status" class="mt-1 block w-full"
            :options="collect($statuses)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="$old('status', $project?->status?->value)" />
        <x-forms.input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="priority" :value="__('Prioridad')" />
        <x-forms.select id="priority" name="priority" class="mt-1 block w-full"
            :options="collect($priorities)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="$old('priority', $project?->priority?->value)" />
        <x-forms.input-error :messages="$errors->get('priority')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="responsible_employee_id" :value="__('Responsable')" />
        <x-forms.select id="responsible_employee_id" name="responsible_employee_id" class="mt-1 block w-full"
            :options="['' => __('— Sin asignar —')] + $employees->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()"
            :selected="$old('responsible_employee_id', $project?->responsible_employee_id)" />
        <x-forms.input-error :messages="$errors->get('responsible_employee_id')" class="mt-2" />
    </div>
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Detalles adicionales') }}</h4>
<div>
    <x-forms.input-label for="budget" :value="__('Presupuesto')" />
    <x-forms.text-input id="budget" name="budget" type="number" step="0.01" min="0" class="mt-1 block w-full"
        :value="$old('budget', $project?->budget)" />
    <x-forms.input-error :messages="$errors->get('budget')" class="mt-2" />
</div>

<div>
    <x-forms.input-label for="observations" :value="__('Observaciones')" />
    <textarea id="observations" name="observations" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ $old('observations', $project?->observations) }}</textarea>
    <x-forms.input-error :messages="$errors->get('observations')" class="mt-2" />
</div>
