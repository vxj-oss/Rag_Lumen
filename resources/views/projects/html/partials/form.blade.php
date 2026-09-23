@php
    $project = $project ?? null;
    $isOldForThisForm = $project
        ? old('_edit_id') == $project->id
        : old('_form') === 'create';
    $old = fn (string $field, $default = null) => $isOldForThisForm ? old($field, $default) : $default;
@endphp

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2">{{ __('Información general') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <x-forms.input-label for="codigo" :value="__('Código')" />
        <x-forms.text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full"
            :value="$old('codigo', $project?->codigo)" required autofocus />
        <x-forms.input-error :messages="$errors->get('codigo')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="nombre" :value="__('Nombre')" />
        <x-forms.text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
            :value="$old('nombre', $project?->nombre)" required />
        <x-forms.input-error :messages="$errors->get('nombre')" class="mt-2" />
    </div>
</div>

<div>
    <x-forms.input-label for="descripcion" :value="__('Descripción')" />
    <textarea id="descripcion" name="descripcion" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ $old('descripcion', $project?->descripcion) }}</textarea>
    <x-forms.input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <x-forms.input-label for="tipo" :value="__('Tipo de proyecto')" />
        <x-forms.select id="tipo" name="tipo" class="mt-1 block w-full"
            :options="collect($types)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="$old('tipo', $project?->tipo?->value)" />
        <x-forms.input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>

    <div x-data="{ quickOpen: false, quickName: '', quickError: '', quickOk: '' }">
        <x-forms.input-label for="cliente_id" :value="__('Cliente')" />
        <div class="flex items-start gap-2">
            <x-forms.select id="cliente_id" name="cliente_id" class="mt-1 block w-full"
                :options="$clients->mapWithKeys(fn ($c) => [$c->id => $c->nombre])"
                :selected="$old('cliente_id', $project?->cliente_id)" />
            <button type="button" @click="quickOpen = !quickOpen" class="mt-1 px-2.5 py-2 rounded-lg text-sm font-medium text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200 hover:bg-emerald-100 transition shrink-0" title="{{ __('Crear cliente rápido') }}">+</button>
        </div>
        <x-forms.input-error :messages="$errors->get('cliente_id')" class="mt-2" />
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
                        body: JSON.stringify({ nombre: quickName.trim(), estado: 'active' }),
                    }).then(async (res) => {
                        const data = await res.json();
                        if (!res.ok) { quickError = data.message || @js(__('No se pudo crear el cliente.')); return; }
                        const sel = document.getElementById('cliente_id');
                        const opt = document.createElement('option');
                        opt.value = data.id; opt.textContent = data.name; sel.appendChild(opt); sel.value = data.id;
                        quickOk = @js(__('Cliente creado y seleccionado.')); quickName = '';
                    }).catch(() => { quickError = @js(__('No se pudo crear el cliente.')); })">{{ __('Guardar cliente') }}</button>
        </div>
    </div>
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Planificación') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <x-forms.input-label for="fecha_inicio" :value="__('Fecha de inicio')" />
        <x-forms.text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full"
            :value="$old('fecha_inicio', $project?->fecha_inicio?->format('Y-m-d'))" required />
        <x-forms.input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="fecha_fin_estimada" :value="__('Fecha fin estimada')" />
        <x-forms.text-input id="fecha_fin_estimada" name="fecha_fin_estimada" type="date" class="mt-1 block w-full"
            :value="$old('fecha_fin_estimada', $project?->fecha_fin_estimada?->format('Y-m-d'))" required />
        <x-forms.input-error :messages="$errors->get('fecha_fin_estimada')" class="mt-2" />
    </div>

    @isset($project)
        <div>
            <x-forms.input-label for="fecha_fin_real" :value="__('Fecha fin real')" />
            <x-forms.text-input id="fecha_fin_real" name="fecha_fin_real" type="date" class="mt-1 block w-full"
                :value="$old('fecha_fin_real', $project?->fecha_fin_real?->format('Y-m-d'))" />
            <x-forms.input-error :messages="$errors->get('fecha_fin_real')" class="mt-2" />
        </div>
    @endisset
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Estado y prioridad') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <x-forms.input-label for="estado" :value="__('Estado')" />
        <x-forms.select id="estado" name="estado" class="mt-1 block w-full"
            :options="collect($statuses)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="$old('estado', $project?->estado?->value)" />
        <x-forms.input-error :messages="$errors->get('estado')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="prioridad" :value="__('Prioridad')" />
        <x-forms.select id="prioridad" name="prioridad" class="mt-1 block w-full"
            :options="collect($priorities)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
            :selected="$old('prioridad', $project?->prioridad?->value)" />
        <x-forms.input-error :messages="$errors->get('prioridad')" class="mt-2" />
    </div>
</div>

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Responsables') }}</h4>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <x-forms.input-label for="empleado_gerente_id" :value="__('Gerente responsable')" />
        <x-forms.select id="empleado_gerente_id" name="empleado_gerente_id" class="mt-1 block w-full"
            :options="['' => __('— Sin asignar —')] + $managers->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()"
            :selected="$old('empleado_gerente_id', $project?->empleado_gerente_id)" />
        <x-forms.input-error :messages="$errors->get('empleado_gerente_id')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="empleado_responsable_id" :value="__('Líder del proyecto')" />
        <x-forms.select id="empleado_responsable_id" name="empleado_responsable_id" class="mt-1 block w-full"
            :options="['' => __('— Sin asignar —')] + $leaders->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])->toArray()"
            :selected="$old('empleado_responsable_id', $project?->empleado_responsable_id)" />
        <x-forms.input-error :messages="$errors->get('empleado_responsable_id')" class="mt-2" />
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
        <span class="text-gray-700">{{ $area->nombre }}</span>
    </label>
    @endforeach
</div>
<x-forms.input-error :messages="$errors->get('area_ids')" class="mt-2" />

<h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider -mb-2 pt-1">{{ __('Detalles adicionales') }}</h4>
<div>
    <x-forms.input-label for="presupuesto" :value="__('Presupuesto')" />
    <x-forms.text-input id="presupuesto" name="presupuesto" type="number" step="0.01" min="0" class="mt-1 block w-full"
        :value="$old('presupuesto', $project?->presupuesto)" />
    <x-forms.input-error :messages="$errors->get('presupuesto')" class="mt-2" />
</div>

<div>
    <x-forms.input-label for="observaciones" :value="__('Observaciones')" />
    <textarea id="observaciones" name="observaciones" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ $old('observaciones', $project?->observaciones) }}</textarea>
    <x-forms.input-error :messages="$errors->get('observaciones')" class="mt-2" />
</div>
