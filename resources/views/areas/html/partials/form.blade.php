@php
    $area = $area ?? null;
@endphp

<div class="grid grid-cols-1 gap-3">
    <div>
        <x-forms.input-label for="nombre" :value="__('Nombre')" />
        <x-forms.text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
            :value="old('nombre', $area?->nombre)" required autofocus />
        <x-forms.input-error :messages="$errors->get('nombre')" class="mt-2" />
    </div>

    <div>
        <x-forms.input-label for="descripcion" :value="__('Descripción')" />
        <textarea id="descripcion" name="descripcion" rows="2"
            class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">{{ old('descripcion', $area?->descripcion) }}</textarea>
        <x-forms.input-error :messages="$errors->get('descripcion')" class="mt-2" />
    </div>

    <div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="active" value="1" {{ old('active', $area?->activa ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
            <span class="text-sm text-gray-700">{{ __('Área activa') }}</span>
        </label>
    </div>
</div>
