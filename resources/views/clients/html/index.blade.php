<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Clientes')" :subtitle="__('Empresas que contratan proyectos')">
            <x-slot name="actions">
                <a href="{{ route('clients.export') }}" class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    <span class="hidden sm:inline">{{ __('Exportar CSV') }}</span>
                </a>
                @can('create', \App\Models\Client::class)
                    <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-client')" class="min-h-[44px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Nuevo cliente') }}
                    </x-ui.primary-button>
                @endcan
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.card padding="p-4">
                <form method="GET" action="{{ route('clients.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Buscar') }}</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Razón social, RUC o contacto...') }}"
                               class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Sector') }}</label>
                        <select name="sector" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($sectors as $sector)
                                <option value="{{ $sector }}" {{ ($filters['sector'] ?? '') === $sector ? 'selected' : '' }}>{{ $sector }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Estado') }}</label>
                        <select name="status" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>{{ __('Activo') }}</option>
                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>{{ __('Inactivo') }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-4 flex items-center gap-3">
                        <button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">{{ __('Filtrar') }}</button>
                        @if (array_filter($filters ?? []))
                            <a href="{{ route('clients.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Limpiar filtros') }}</a>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card padding="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Razón social') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Contacto') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Sector') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Proyectos') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($clients as $client)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('clients.show', $client) }}" class="text-sm font-medium text-emerald-600 hover:underline">{{ $client->name }}</a>
                                        @if ($client->tax_id)
                                            <div class="text-xs text-gray-400">{{ $client->tax_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $client->contact_name ?? '—' }}
                                        @if ($client->email)
                                            <div class="text-xs text-gray-400">{{ $client->email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $client->sector ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $client->projects_count }}</td>
                                    <td class="px-6 py-4">
                                        <x-ui.badge :color="$client->status === 'active' ? 'green' : 'gray'">
                                            {{ $client->status === 'active' ? __('Activo') : __('Inactivo') }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('update', $client)
                                                <button x-data @click="$dispatch('open-modal', 'edit-client-{{ $client->id }}')" class="text-emerald-600 hover:text-emerald-800 font-medium transition">{{ __('Editar') }}</button>
                                            @endcan
                                            @can('delete', $client)
                                                <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline" @submit.prevent="if (confirm(@js(__('¿Eliminar este cliente?')))) $el.submit()">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium transition">{{ __('Eliminar') }}</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                @can('update', $client)
                                    <x-ui.form-modal :name="'edit-client-'.$client->id" :title="__('Editar cliente')"
                                        :subtitle="$client->name" :action="route('clients.update', $client)" method="PUT" max-width="2xl">
                                        <x-slot name="hidden"><input type="hidden" name="_edit_id" value="{{ $client->id }}"></x-slot>
                                        @include('clients.html.partials.form')
                                    </x-ui.form-modal>
                                @endcan
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-500">{{ __('Todavía no hay clientes registrados.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{ $clients->links() }}
        </div>
    </div>

    @can('create', \App\Models\Client::class)
        <x-ui.form-modal name="create-client" :title="__('Nuevo cliente')" :subtitle="__('Registra una empresa cliente')"
            :action="route('clients.store')" max-width="2xl" submit-label="{{ __('Crear cliente') }}">
            <x-slot name="hidden"><input type="hidden" name="_form" value="create"></x-slot>
            @php $client = null; @endphp
            @include('clients.html.partials.form')
        </x-ui.form-modal>
    @endcan

    @if ($errors->any())
        @php $modalToOpen = old('_edit_id') ? 'edit-client-'.old('_edit_id') : (old('_form') === 'create' ? 'create-client' : null); @endphp
        @if ($modalToOpen)
            <div x-data x-init="$dispatch('open-modal', @js($modalToOpen))"></div>
        @endif
    @endif
</x-app-layout>
