<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Alertas')" :subtitle="__('Avisos generados automáticamente sobre proyectos, tareas y equipo')">
            @if ($unreadCount > 0)
                <x-slot name="actions">
                    <form action="{{ route('alerts.read-all') }}" method="POST">
                        @csrf
                        <x-ui.secondary-button type="submit" class="min-h-[44px]">{{ __('Marcar todas como leídas') }}</x-ui.secondary-button>
                    </form>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-ui.card padding="p-0">
                <ul class="divide-y divide-gray-100">
                    @forelse ($notifications as $notification)
                        @php
                            $link = match ($notification->data['type'] ?? null) {
                                'project_high_risk', 'project_delayed' => route('projects.show', $notification->data['project_id']),
                                'task_due_soon' => route('tasks.show', $notification->data['task_id']),
                                'employee_overloaded' => route('employees.show', $notification->data['employee_id']),
                                default => null,
                            };
                        @endphp
                        <li class="p-4 flex items-start gap-3 {{ $notification->read_at ? '' : 'bg-emerald-50/50' }}">
                            <div class="w-2 h-2 rounded-full mt-2 shrink-0 {{ $notification->read_at ? 'bg-gray-300' : 'bg-emerald-500' }}"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800">
                                    @if ($link)
                                        <a href="{{ $link }}" class="hover:underline">{{ $notification->data['message'] ?? __('Notificación') }}</a>
                                    @else
                                        {{ $notification->data['message'] ?? __('Notificación') }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @unless ($notification->read_at)
                                <form action="{{ route('alerts.read', $notification->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs text-emerald-600 hover:underline whitespace-nowrap">{{ __('Marcar leída') }}</button>
                                </form>
                            @endunless
                        </li>
                    @empty
                        <li class="p-10 text-center text-sm text-gray-500">{{ __('No tienes alertas por el momento.') }}</li>
                    @endforelse
                </ul>
            </x-ui.card>

            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
