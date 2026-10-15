@php $falta = $falta ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <x-forms.input-label :value="__('Código')" />
        <x-forms.text-input name="codigo" type="text" class="mt-1 block w-full" placeholder="F-01"
            :value="old('codigo', $falta?->codigo)" required />
        <x-forms.input-error :messages="$errors->get('codigo')" class="mt-2" />
    </div>
    <div class="sm:col-span-2">
        <x-forms.input-label :value="__('Falta')" />
        <x-forms.text-input name="titulo" type="text" class="mt-1 block w-full" :value="old('titulo', $falta?->titulo)" required />
        <x-forms.input-error :messages="$errors->get('titulo')" class="mt-2" />
    </div>
</div>
<div>
    <x-forms.input-label :value="__('Descripción de la conducta')" />
    <textarea name="descripcion" rows="3" required
        class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">{{ old('descripcion', $falta?->descripcion) }}</textarea>
    <x-forms.input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <x-forms.input-label :value="__('Aplica a')" />
        <x-forms.select name="area" class="mt-1 block w-full"
            :options="['' => __('General (todas las áreas)')] + \App\Models\TerminosReferencia::AREAS"
            :selected="old('area', $falta?->area ?? ($area ?? ''))" />
        <x-forms.input-error :messages="$errors->get('area')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label :value="__('Gravedad')" />
        <x-forms.select name="gravedad" class="mt-1 block w-full" :options="$gravedades" :selected="old('gravedad', $falta?->gravedad ?? 'leve')" />
        <x-forms.input-error :messages="$errors->get('gravedad')" class="mt-2" />
    </div>
    <div>
        <x-forms.input-label :value="__('Monto a resarcir en S/ (opcional)')" />
        <x-forms.text-input name="monto" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('monto', $falta?->monto)" />
        <x-forms.input-error :messages="$errors->get('monto')" class="mt-2" />
    </div>
</div>
<div>
    <x-forms.input-label :value="__('Sanción')" />
    <textarea name="sancion" rows="2" placeholder="{{ __('Ej: Amonestación escrita; reincidencia: resolución del convenio') }}"
        class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">{{ old('sancion', $falta?->sancion) }}</textarea>
    <x-forms.input-error :messages="$errors->get('sancion')" class="mt-2" />
</div>
<label class="inline-flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="activa" value="1" @checked(old('activa', $falta?->activa ?? true)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
    {{ __('Falta vigente (aparece en el manual y en las actas)') }}
</label>
