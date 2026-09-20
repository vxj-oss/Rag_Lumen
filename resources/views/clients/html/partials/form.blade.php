@php
    $client = $client ?? null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <x-forms.input-label for="name" :value="__('Razón social')" />
        <x-forms.text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $client?->name)" required autofocus />
        <x-forms.input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="tax_id" :value="__('RUC / Identificador fiscal')" />
        <x-forms.text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full"
            :value="old('tax_id', $client?->tax_id)" />
        <x-forms.input-error :messages="$errors->get('tax_id')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="sector" :value="__('Sector')" />
        <x-forms.text-input id="sector" name="sector" type="text" class="mt-1 block w-full"
            :value="old('sector', $client?->sector)" placeholder="{{ __('ej. Retail, Energía, Gastronomía') }}" />
        <x-forms.input-error :messages="$errors->get('sector')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="contact_name" :value="__('Contacto principal')" />
        <x-forms.text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full"
            :value="old('contact_name', $client?->contact_name)" />
        <x-forms.input-error :messages="$errors->get('contact_name')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="phone" :value="__('Teléfono')" />
        <x-forms.text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
            :value="old('phone', $client?->phone)" />
        <x-forms.input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-forms.input-label for="email" :value="__('Email')" />
        <x-forms.text-input id="email" name="email" type="email" class="mt-1 block w-full"
            :value="old('email', $client?->email)" />
        <x-forms.input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="status" :value="__('Estado')" />
        <x-forms.select id="status" name="status" class="mt-1 block w-full"
            :options="['active' => __('Activo'), 'inactive' => __('Inactivo')]"
            :selected="old('status', $client?->status ?? 'active')" />
        <x-forms.input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>
