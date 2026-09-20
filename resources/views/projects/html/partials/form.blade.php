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

    <div x-data="{ quickOpen: false, quickName: '', quickError: '', quickOk: '' }">
        <x-forms.input-label for="client_id" :value="__('Cliente')" />
        <div class="flex items-start gap-2">
            <x-forms.select id="client_id" name="client_id" class="mt-1 block w-full"
                :options="$clients->mapWithKeys(fn ($c) => [$c->id => $c->name])"
                :selected="$old('client_id', $project?->client_id)" />
            <button type="button" @click="quickOpen = !quickOpen" class="mt-1 px-2.5 py-2 rounded-lg text-sm font-medium text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200 hover:bg-emerald-100 transition shrink-0" title="{{ __('Crear cliente rápido') }}">+</button>
        </div>
        <x-forms.input-error :messages="$errors->get('client_id')" class="mt-2" />
        <div x-show="quickOpen" x-cloak class="mt-2 p-3 rounded-lg bg-gray-50 ring-1 ring-gray-200 space-y-2">
            <x-forms.text-input type="text" x-model="quickName" placeholder="{{ __('Nombre del nuevo cliente') }}" class="block w-full" />
            <p x-show="quickError" x-text="quickError" class="text-xs text-red-600"></p>
            <p x-show="quickOk" x-text="quickOk" class="text-xs text-emerald-600"></p>
            <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition"
                @click="quickError = ''; quickOk = '';
                    if (!quickName.trim()) { quickError = @js(__('Escribe el nombre del cliente.')); return; }
                    fetch(@js(route('clients.quick')), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
                        body: JSON.stringify({ name: quickName.trim(), status: 'active' }),
                    }).then(async (res) => {
                        const data = await res.json();
                        if (!res.ok) { quickError = data.message || @js(__('No se pudo crear el cliente.')); return; }
                        const sel = document.getElementById('client_id');
                        const opt = document.createElement('option');
                        opt.value = data.id; opt.textContent = data.name; sel.appendChild(opt); sel.value = data.id;
                        quickOk = @js(__('Cliente creado y seleccionado.')); quickName = '';
                    }).catch(() => { quickError = @js(__('No se pudo crear el cliente.')); })">{{ __('Guardar cliente') }}</button>
        </div>
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

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Estado y prioridad') }}</h4>
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
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Responsables') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-forms.input-label for="manager_employee_id" :value="__('Gerente responsable')" />
        <x-forms.select id="manager_employee_id" name="manager_employee_id" class="mt-1 block w-full"
            :options="['' => __('— Sin asignar —')] + $managers->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()"
            :selected="$old('manager_employee_id', $project?->manager_employee_id)" />
        <x-forms.input-error :messages="$errors->get('manager_employee_id')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="responsible_employee_id" :value="__('Líder del proyecto')" />
        <x-forms.select id="responsible_employee_id" name="responsible_employee_id" class="mt-1 block w-full"
            :options="['' => __('— Sin asignar —')] + $leaders->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()"
            :selected="$old('responsible_employee_id', $project?->responsible_employee_id)" />
        <x-forms.input-error :messages="$errors->get('responsible_employee_id')" class="mt-2" />
    </div>
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Áreas participantes') }}</h4>
@php
    $selectedAreas = $isOldForThisForm
        ? (array) old('area_ids', $project?->areas->pluck('id')->all() ?? [])
        : ($project?->areas->pluck('id')->all() ?? []);
    $selectedAreas = array_map('strval', $selectedAreas);
@endphp
<div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
    @foreach ($areas as $area)
    <label class="flex items-center gap-2 px-3 py-2 rounded-lg ring-1 ring-gray-200 bg-white hover:ring-emerald-300 cursor-pointer transition text-sm">
        <input type="checkbox" name="area_ids[]" value="{{ $area->id }}"
            {{ in_array((string) $area->id, $selectedAreas, true) ? 'checked' : '' }}
            class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
        <span class="text-gray-700">{{ $area->name }}</span>
    </label>
    @endforeach
</div>
<x-forms.input-error :messages="$errors->get('area_ids')" class="mt-2" />

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
