<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Estados de :project', ['project' => $project->name])" :subtitle="__('Flujo de estados propio de este proyecto')">
            <x-slot name="actions">
                <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-state')" class="min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('Nuevo estado') }}
                </x-ui.primary-button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    {{ __('← Volver al proyecto') }}
                </a>
            </div>
            <x-ui.card padding="p-0">
                @php
                    $dots = [
                        'gray' => 'bg-gray-400', 'blue' => 'bg-blue-500', 'indigo' => 'bg-indigo-500',
                        'green' => 'bg-emerald-500', 'yellow' => 'bg-yellow-400', 'red' => 'bg-red-500',
                        'orange' => 'bg-orange-500', 'amber' => 'bg-amber-400',
                    ];
                @endphp
                <ul class="divide-y divide-gray-100">
                    @forelse ($states as $index => $state)
                        <li class="p-4 flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full shrink-0 {{ $dots[$state->color] ?? 'bg-gray-400' }}"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $index + 1 }}. {{ $state->name }}
                                    <span class="text-xs text-gray-400 font-mono">{{ $state->slug }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    @if ($state->is_initial) {{ __('Inicial') }} · @endif
                                    @if ($state->is_final) {{ __('Final') }} · @endif
                                    @if ($state->is_blocking) {{ __('Bloqueo') }} · @endif
                                    {{ $state->active ? __('Activo') : __('Inactivo') }} ·
                                    {{ __(':count tareas', ['count' => $state->tasks()->count()]) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($index > 0)
                                    <form action="{{ route('projects.statuses.reorder', $project) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="ordered_ids" value="">
                                        <button type="submit" class="move-up p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition" title="{{ __('Subir') }}"
                                            data-order='@js($states->pluck("id"))' data-pos="{{ $index }}">↑</button>
                                    </form>
                                @endif
                                <button x-data @click="$dispatch('open-modal', 'edit-state-{{ $state->id }}')" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">{{ __('Editar') }}</button>
                                <form action="{{ route('projects.statuses.destroy', [$project, $state]) }}" method="POST" class="inline"
                                    @submit.prevent="if (confirm(@js(__('¿Eliminar este estado?')))) $el.submit()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">{{ __('Eliminar') }}</button>
                                </form>
                            </div>
                        </li>

                        <x-ui.form-modal :name="'edit-state-'.$state->id" :title="__('Editar estado')"
                            :subtitle="$state->name" :action="route('projects.statuses.update', [$project, $state])" method="PUT">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <x-forms.input-label for="name" :value="__('Nombre')" />
                                    <x-forms.text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                        :value="old('name', $state->name)" required />
                                    <x-forms.input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-forms.input-label for="color" :value="__('Color')" />
                                    <x-forms.select id="color" name="color" class="mt-1 block w-full"
                                        :options="['gray' => __('Gris'), 'blue' => __('Azul'), 'indigo' => __('Índigo'), 'green' => __('Verde'), 'yellow' => __('Amarillo'), 'red' => __('Rojo'), 'orange' => __('Naranja'), 'amber' => __('Ámbar')]"
                                        :selected="old('color', $state->color)" />
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="is_initial" value="1" {{ old('is_initial', $state->is_initial) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                        {{ __('Es inicial') }}
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="is_final" value="1" {{ old('is_final', $state->is_final) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                        {{ __('Es final') }}
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="is_blocking" value="1" {{ old('is_blocking', $state->is_blocking) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                        {{ __('Es bloqueo') }}
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="active" value="1" {{ old('active', $state->active) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                        {{ __('Activo') }}
                                    </label>
                                </div>
                            </div>
                        </x-ui.form-modal>
                    @empty
                        <li class="p-6 text-sm text-gray-500 text-center">{{ __('Sin estados personalizados.') }}</li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </div>
        </div>
    </div>

    <x-ui.form-modal name="create-state" :title="__('Nuevo estado')" :subtitle="$project->name"
        :action="route('projects.statuses.store', $project)" submit-label="{{ __('Crear estado') }}">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <x-forms.input-label for="name" :value="__('Nombre')" />
                <x-forms.text-input id="name" name="name" type="text" class="mt-1 block w-full"
                    :value="old('name')" required autofocus placeholder="{{ __('ej. Esperando cliente') }}" />
                <x-forms.input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-forms.input-label for="color" :value="__('Color')" />
                <x-forms.select id="color" name="color" class="mt-1 block w-full"
                    :options="['gray' => __('Gris'), 'blue' => __('Azul'), 'indigo' => __('Índigo'), 'green' => __('Verde'), 'yellow' => __('Amarillo'), 'red' => __('Rojo'), 'orange' => __('Naranja'), 'amber' => __('Ámbar')]"
                    :selected="old('color', 'gray')" />
            </div>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_initial" value="1" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                    {{ __('Es inicial') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_final" value="1" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                    {{ __('Es final') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_blocking" value="1" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                    {{ __('Es bloqueo') }}
                </label>
            </div>
        </div>
    </x-ui.form-modal>

    <script>
        document.querySelectorAll('.move-up').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const order = JSON.parse(btn.dataset.order);
                const pos = parseInt(btn.dataset.pos, 10);
                [order[pos - 1], order[pos]] = [order[pos], order[pos - 1]];
                const form = btn.closest('form');
                form.querySelector('input[name="ordered_ids"]').remove();
                order.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ordered_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
            });
        });
    </script>
</x-app-layout>
