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
            <x-ui.card>
                <h2 class="text-lg font-bold text-slate-900">{{ __('Situaciones que requieren atención') }} · {{ count($attentionItems) }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('Es el mismo indicador de tu jornada en el dashboard. Se calcula con los datos actuales; no depende de las notificaciones guardadas ni de haberlas leído.') }}</p>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse($attentionItems as $item)
                        <li class="py-3"><a href="{{ $item['url'] }}" class="font-semibold text-emerald-800 hover:underline">{{ $item['title'] }}</a><p class="mt-1 text-sm text-slate-600">{{ $item['reason'] }}</p></li>
                    @empty
                        <li class="py-3 text-sm text-slate-600">{{ __('No hay situaciones activas que requieran atención en tu jornada.') }}</li>
                    @endforelse
                </ul>
            </x-ui.card>
            <h2 class="text-lg font-bold text-slate-900">{{ __('Notificaciones guardadas') }} · {{ $unreadCount }} {{ __('sin leer') }}</h2>
            <details class="rounded-xl border border-slate-200 bg-white p-4"><summary class="cursor-pointer text-sm font-semibold">{{ __('Mis preferencias de notificación') }}</summary><form method="POST" action="{{ route('alerts.preferences') }}" class="mt-3 flex flex-wrap items-end gap-4">@csrf @method('PUT')<label class="text-sm">{{ __('Dentro de la web') }}<select name="notify_in_app" class="ml-2 rounded-lg border-slate-300"><option value="1" @selected(auth()->user()->notify_in_app)>{{ __('Activadas') }}</option><option value="0" @selected(!auth()->user()->notify_in_app)>{{ __('Desactivadas') }}</option></select></label><label class="text-sm">{{ __('Por correo') }}<select name="notify_by_email" class="ml-2 rounded-lg border-slate-300"><option value="0" @selected(!auth()->user()->notify_by_email)>{{ __('Desactivadas') }}</option><option value="1" @selected(auth()->user()->notify_by_email)>{{ __('Activadas') }}</option></select></label><x-ui.secondary-button type="submit">{{ __('Guardar preferencias') }}</x-ui.secondary-button></form><p class="mt-2 text-xs text-slate-600">{{ __('El correo requiere que el servicio SMTP esté configurado. Desactivar notificaciones no elimina las anteriores.') }}</p></details>
            <x-ui.card padding="p-0">
                <ul class="divide-y divide-slate-100">
                    @forelse ($notifications as $notification)
                        @php
                            $link = match ($notification->data['type'] ?? null) {
                                'project_high_risk', 'project_delayed' => route('projects.show', $notification->data['project_id']),
                                'task_due_soon', 'task_event' => route('tasks.show', $notification->data['task_id']),
                                'employee_overloaded' => route('employees.show', $notification->data['employee_id']),
                                default => null,
                            };
                        @endphp
                        <li class="p-4 flex items-start gap-3 {{ $notification->read_at ? '' : 'bg-emerald-50/50' }}">
                            <div class="w-2 h-2 rounded-full mt-2 shrink-0 {{ $notification->read_at ? 'bg-slate-300' : 'bg-emerald-500' }}"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-800">
                                    @if ($link)
                                        <a href="{{ $link }}" class="hover:underline">{{ $notification->data['message'] ?? __('Notificación') }}</a>
                                    @else
                                        {{ $notification->data['message'] ?? __('Notificación') }}
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @unless ($notification->read_at)
                                <form action="{{ route('alerts.read', $notification->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs text-emerald-600 hover:underline whitespace-nowrap">{{ __('Marcar leída') }}</button>
                                </form>
                            @endunless
                        </li>
                    @empty
                        <li class="p-10 text-center text-sm text-slate-500">{{ __('No hay notificaciones guardadas por el momento. Las situaciones actuales se muestran arriba.') }}</li>
                    @endforelse
                </ul>
            </x-ui.card>

            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
