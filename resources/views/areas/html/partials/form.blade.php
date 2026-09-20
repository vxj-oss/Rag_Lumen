@php
    $area = $area ?? null;
@endphp

<div class="grid grid-cols-1 gap-4">
    <div>
        <x-forms.input-label for="name" :value="__('Nombre')" />
        <x-forms.text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $area?->name)" required autofocus />
        <x-forms.input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="description" :value="__('Descripción')" />
        <textarea id="description" name="description" rows="2"
            class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ old('description', $area?->description) }}</textarea>
        <x-forms.input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="active" value="1" {{ old('active', $area?->active ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
            <span class="text-sm text-gray-700">{{ __('Área activa') }}</span>
        </label>
    </div>
</div>
