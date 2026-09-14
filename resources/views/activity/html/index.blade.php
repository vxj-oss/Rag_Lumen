<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Actividad') }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('Registro de quién cambió qué y cuándo') }}</p>
        </div>
    </x-slot>

    @php
        $actionLabels = [
            'created' => __('Creación'),
            'updated' => __('Edición'),
            'deleted' => __('Eliminación'),
            'progress_recorded' => __('Avance registrado'),
        ];
        $actionColors = [
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'progress_recorded' => 'indigo',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-ui.card padding="p-4">
                <form method="GET" action="{{ route('activity.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Tipo de acción') }}</label>
                        <select name="action" class="w-48 border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>{{ $actionLabels[$action] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">{{ __('Filtrar') }}</button>
                    @if (array_filter($filters ?? []))
                        <a href="{{ route('activity.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Limpiar') }}</a>
                    @endif
                </form>
            </x-ui.card>

            <x-ui.card padding="p-0">
                <ul class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <li class="p-4 flex items-start gap-3">
                            <x-ui.badge :color="$actionColors[$log->action] ?? 'gray'" class="mt-0.5 shrink-0">
                                {{ $actionLabels[$log->action] ?? $log->action }}
                            </x-ui.badge>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800">{{ $log->description }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $log->user?->name ?? __('Sistema') }} · {{ $log->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="p-10 text-center text-sm text-gray-500">{{ __('Todavía no hay actividad registrada.') }}</li>
                    @endforelse
                </ul>
            </x-ui.card>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
