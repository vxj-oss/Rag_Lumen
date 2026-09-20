<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Áreas')" :subtitle="__('Departamentos de la empresa')">
            @can('create', \App\Models\Area::class)
                <x-slot name="actions">
                    <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-area')" class="min-h-[44px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Nueva área') }}
                    </x-ui.primary-button>
                </x-slot>
            @endcan
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.card padding="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Descripción') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Empleados') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Proyectos') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($areas as $area)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $area->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $area->description ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $area->employees_count }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $area->projects_count }}</td>
                                    <td class="px-6 py-4">
                                        <x-ui.badge :color="$area->active ? 'green' : 'gray'">
                                            {{ $area->active ? __('Activa') : __('Inactiva') }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('update', $area)
                                                <button x-data @click="$dispatch('open-modal', 'edit-area-{{ $area->id }}')" class="text-emerald-600 hover:text-emerald-800 font-medium transition">{{ __('Editar') }}</button>
                                            @endcan
                                            @can('delete', $area)
                                                <form action="{{ route('areas.destroy', $area) }}" method="POST" class="inline" @submit.prevent="if (confirm(@js(__('¿Eliminar esta área?')))) $el.submit()">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium transition">{{ __('Eliminar') }}</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                @can('update', $area)
                                    <x-ui.form-modal :name="'edit-area-'.$area->id" :title="__('Editar área')"
                                        :subtitle="$area->name" :action="route('areas.update', $area)" method="PUT" max-width="2xl">
                                        <x-slot name="hidden"><input type="hidden" name="_edit_id" value="{{ $area->id }}"></x-slot>
                                        @include('areas.html.partials.form')
                                    </x-ui.form-modal>
                                @endcan
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-500">{{ __('Todavía no hay áreas registradas.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>

    @can('create', \App\Models\Area::class)
        <x-ui.form-modal name="create-area" :title="__('Nueva área')" :subtitle="__('Crea un departamento de la empresa')"
            :action="route('areas.store')" max-width="2xl" submit-label="{{ __('Crear área') }}">
            <x-slot name="hidden"><input type="hidden" name="_form" value="create"></x-slot>
            @php $area = null; @endphp
            @include('areas.html.partials.form')
        </x-ui.form-modal>
    @endcan

    @if ($errors->any())
        @php $modalToOpen = old('_edit_id') ? 'edit-area-'.old('_edit_id') : (old('_form') === 'create' ? 'create-area' : null); @endphp
        @if ($modalToOpen)
            <div x-data x-init="$dispatch('open-modal', @js($modalToOpen))"></div>
        @endif
    @endif
</x-app-layout>
