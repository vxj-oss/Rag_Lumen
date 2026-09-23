@php
    $employee = $employee ?? null;
@endphp

<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-semibold shrink-0">
            {{ Str::of(old('nombres', $employee?->nombres) ?: '?')->substr(0, 1) }}
        </div>
        <p class="text-xs text-gray-500">{{ __('Los datos se usarán para identificar al empleado en proyectos y tareas.') }}</p>
    </div>

    <h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">{{ __('Información personal') }}</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <x-forms.input-label for="nombres" :value="__('Nombre')" />
            <x-forms.text-input id="nombres" name="nombres" type="text" class="mt-1 block w-full"
                :value="old('nombres', $employee?->nombres)" required autofocus />
            <x-forms.input-error :messages="$errors->get('nombres')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="apellidos" :value="__('Apellido')" />
            <x-forms.text-input id="apellidos" name="apellidos" type="text" class="mt-1 block w-full"
                :value="old('apellidos', $employee?->apellidos)" required />
            <x-forms.input-error :messages="$errors->get('apellidos')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="correo" :value="__('Email')" />
            <x-forms.text-input id="correo" name="correo" type="email" class="mt-1 block w-full"
                :value="old('correo', $employee?->correo)" required />
            <x-forms.input-error :messages="$errors->get('correo')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="telefono" :value="__('Teléfono')" />
            <x-forms.text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full"
                :value="old('telefono', $employee?->telefono)" />
            <x-forms.input-error :messages="$errors->get('telefono')" class="mt-2" />
        </div>
    </div>
</div>

<div>
    <h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">{{ __('Detalles laborales') }}</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <x-forms.input-label for="cargo" :value="__('Cargo')" />
            <x-forms.text-input id="cargo" name="cargo" type="text" class="mt-1 block w-full"
                :value="old('cargo', $employee?->cargo)" />
            <x-forms.input-error :messages="$errors->get('cargo')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="especialidad" :value="__('Especialidad')" />
            <x-forms.select id="especialidad" name="especialidad" class="mt-1 block w-full"
                :options="collect($specialties)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
                :selected="old('especialidad', $employee?->especialidad?->value)" />
            <x-forms.input-error :messages="$errors->get('especialidad')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="area_id" :value="__('Área')" />
            <x-forms.select id="area_id" name="area_id" class="mt-1 block w-full"
                :options="['' => __('— Sin área —')] + $areas->mapWithKeys(fn ($a) => [$a->id => $a->nombre])->toArray()"
                :selected="old('area_id', $employee?->area_id)" />
            <x-forms.input-error :messages="$errors->get('area_id')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="estado" :value="__('Estado')" />
            <x-forms.select id="estado" name="estado" class="mt-1 block w-full"
                :options="collect($statuses)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
                :selected="old('estado', $employee?->estado?->value)" />
            <x-forms.input-error :messages="$errors->get('estado')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="fecha_contratacion" :value="__('Fecha de ingreso')" />
            <x-forms.text-input id="fecha_contratacion" name="fecha_contratacion" type="date" class="mt-1 block w-full"
                :value="old('fecha_contratacion', $employee?->fecha_contratacion?->format('Y-m-d'))" />
            <x-forms.input-error :messages="$errors->get('fecha_contratacion')" class="mt-2" />
        </div>
    </div>
</div>

<div x-data="{ createAccess: false }">
    <h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">{{ __('Acceso al sistema') }}</h4>

    @if ($employee?->usuario_id !== null)
        <div class="space-y-3">
            <p class="text-sm text-gray-600">
                {{ __('Ya tiene acceso al sistema con el correo') }} <strong>{{ $employee->user?->email }}</strong>.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-forms.input-label for="password" :value="__('Nueva contraseña (opcional)')" />
                    <x-forms.text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    <p class="text-xs text-gray-400 mt-1">{{ __('Déjalo en blanco para no cambiarla.') }}</p>
                    <x-forms.input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-forms.input-label for="password_confirmation" :value="__('Confirmar nueva contraseña')" />
                    <x-forms.text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                </div>
            </div>
        </div>
    @else
        <div class="space-y-3">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="create_access" value="1" x-model="createAccess"
                       class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                <span class="text-sm text-gray-700">{{ __('Crear acceso al sistema (usuario y contraseña)') }}</span>
            </label>

            <div x-show="createAccess" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-forms.input-label for="password" :value="__('Contraseña')" />
                    <x-forms.text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    <x-forms.input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-forms.input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                    <x-forms.text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                </div>
            </div>
            <p class="text-xs text-gray-400">{{ __('El empleado usará este correo y contraseña para iniciar sesión.') }}</p>
        </div>
    @endif
</div>
