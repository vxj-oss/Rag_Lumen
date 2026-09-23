@php
    $client = $client ?? null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div class="sm:col-span-2">
        <x-forms.input-label for="nombre" :value="__('Razón social')" />
        <x-forms.text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
            :value="old('nombre', $client?->nombre)" required autofocus />
        <x-forms.input-error :messages="$errors->get('nombre')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="identificacion_fiscal" :value="__('RUC / Identificador fiscal')" />
        <x-forms.text-input id="identificacion_fiscal" name="identificacion_fiscal" type="text" class="mt-1 block w-full"
            :value="old('identificacion_fiscal', $client?->identificacion_fiscal)" />
        <x-forms.input-error :messages="$errors->get('identificacion_fiscal')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="sector" :value="__('Sector')" />
        <x-forms.text-input id="sector" name="sector" type="text" class="mt-1 block w-full"
            :value="old('sector', $client?->sector)" placeholder="{{ __('ej. Retail, Energía, Gastronomía') }}" />
        <x-forms.input-error :messages="$errors->get('sector')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="nombre_contacto" :value="__('Contacto principal')" />
        <x-forms.text-input id="nombre_contacto" name="nombre_contacto" type="text" class="mt-1 block w-full"
            :value="old('nombre_contacto', $client?->nombre_contacto)" />
        <x-forms.input-error :messages="$errors->get('nombre_contacto')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="telefono" :value="__('Teléfono')" />
        <x-forms.text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full"
            :value="old('telefono', $client?->telefono)" />
        <x-forms.input-error :messages="$errors->get('telefono')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-forms.input-label for="correo" :value="__('Email')" />
        <x-forms.text-input id="correo" name="correo" type="email" class="mt-1 block w-full"
            :value="old('correo', $client?->correo)" />
        <x-forms.input-error :messages="$errors->get('correo')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="estado" :value="__('Estado')" />
        <x-forms.select id="estado" name="estado" class="mt-1 block w-full"
            :options="['active' => __('Activo'), 'inactive' => __('Inactivo')]"
            :selected="old('estado', $client?->estado ?? 'active')" />
        <x-forms.input-error :messages="$errors->get('estado')" class="mt-2" />
    </div>
</div>
