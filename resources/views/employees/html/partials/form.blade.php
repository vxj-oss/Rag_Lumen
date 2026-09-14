@php
    $employee = $employee ?? null;
@endphp

<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-semibold shrink-0">
            {{ Str::of(old('first_name', $employee?->first_name) ?: '?')->substr(0, 1) }}
        </div>
        <p class="text-xs text-gray-500">{{ __('Los datos se usarán para identificar al empleado en proyectos y tareas.') }}</p>
    </div>

    <h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">{{ __('Información personal') }}</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-forms.input-label for="first_name" :value="__('Nombre')" />
            <x-forms.text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full"
                :value="old('first_name', $employee?->first_name)" required autofocus />
            <x-forms.input-error :messages="$errors->get('first_name')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="last_name" :value="__('Apellido')" />
            <x-forms.text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full"
                :value="old('last_name', $employee?->last_name)" required />
            <x-forms.input-error :messages="$errors->get('last_name')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="email" :value="__('Email')" />
            <x-forms.text-input id="email" name="email" type="email" class="mt-1 block w-full"
                :value="old('email', $employee?->email)" required />
            <x-forms.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="phone" :value="__('Teléfono')" />
            <x-forms.text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                :value="old('phone', $employee?->phone)" />
            <x-forms.input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
    </div>
</div>

<div>
    <h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">{{ __('Detalles laborales') }}</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-forms.input-label for="position" :value="__('Cargo')" />
            <x-forms.text-input id="position" name="position" type="text" class="mt-1 block w-full"
                :value="old('position', $employee?->position)" />
            <x-forms.input-error :messages="$errors->get('position')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="specialty" :value="__('Especialidad')" />
            <x-forms.select id="specialty" name="specialty" class="mt-1 block w-full"
                :options="collect($specialties)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
                :selected="old('specialty', $employee?->specialty?->value)" />
            <x-forms.input-error :messages="$errors->get('specialty')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="status" :value="__('Estado')" />
            <x-forms.select id="status" name="status" class="mt-1 block w-full"
                :options="collect($statuses)->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
                :selected="old('status', $employee?->status?->value)" />
            <x-forms.input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
        <div>
            <x-forms.input-label for="hire_date" :value="__('Fecha de ingreso')" />
            <x-forms.text-input id="hire_date" name="hire_date" type="date" class="mt-1 block w-full"
                :value="old('hire_date', $employee?->hire_date?->format('Y-m-d'))" />
            <x-forms.input-error :messages="$errors->get('hire_date')" class="mt-2" />
        </div>
    </div>
</div>

<div x-data="{ createAccess: false }">
    <h4 class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">{{ __('Acceso al sistema') }}</h4>

    @if ($employee?->user_id !== null)
        <div class="space-y-4">
            <p class="text-sm text-gray-600">
                {{ __('Ya tiene acceso al sistema con el correo') }} <strong>{{ $employee->user?->email }}</strong>.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
        <div class="space-y-4">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="create_access" value="1" x-model="createAccess"
                       class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                <span class="text-sm text-gray-700">{{ __('Crear acceso al sistema (usuario y contraseña)') }}</span>
            </label>

            <div x-show="createAccess" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
