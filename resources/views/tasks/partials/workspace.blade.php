<div x-show="viewMode === 'list' && !loading && visibleCards().length" x-cloak class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-left text-sm">
        <caption class="sr-only">{{ __('Actividades de la vista seleccionada') }}</caption>
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-5 py-4">{{ __('Actividad') }}</th><th class="px-5 py-4">{{ __('Responsable') }}</th><th class="px-5 py-4">{{ __('Estado') }}</th><th class="px-5 py-4">{{ __('Vencimiento') }}</th><th class="px-5 py-4">{{ __('Avance') }}</th><th class="px-5 py-4"><span class="sr-only">{{ __('Acciones') }}</span></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            <template x-for="card in pageCards()" :key="card.id">
                <tr class="hover:bg-emerald-50/40">
                    <td class="min-w-[240px] max-w-sm px-5 py-4"><button type="button" @click="openDetail(card)" class="text-left font-semibold text-slate-900 hover:text-emerald-800" x-text="card.title"></button><p class="mt-1 text-xs text-slate-600" x-text="card.code + ' · ' + card.project_name"></p></td>
                    <td class="px-5 py-4" x-text="card.assignee_name || 'Sin asignar'"></td>
                    <td class="px-5 py-4"><span class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold"><span class="h-2 w-2 rounded-full" :class="dotClass(columns.find(c => c.slug === card.status_slug)?.color)"></span><span x-text="card.status_label"></span></span></td>
                    <td class="whitespace-nowrap px-5 py-4" :class="card.overdue && 'font-semibold text-rose-700'" x-text="card.due?.label || 'Sin fecha'"></td>
                    <td class="px-5 py-4"><span class="font-semibold tabular-nums" x-text="card.progress + '%'"></span></td>
                    <td class="px-5 py-4"><button type="button" class="workspace-button" @click="openDetail(card)">{{ __('Revisar') }}</button></td>
                </tr>
            </template>
        </tbody>
    </table>
</div>

<x-ui.modal name="task-preview" position="right" max-width="xl" :label="__('Vista rápida de actividad')" focusable>
    <template x-if="selectedCard">
        <div class="flex min-h-0 flex-1 flex-col">
            <div class="flex items-center justify-between border-b border-slate-200 p-5"><span class="workspace-eyebrow mb-0">{{ __('Vista rápida') }}</span><button type="button" @click="$dispatch('close')" class="workspace-button" aria-label="{{ __('Cerrar detalle') }}">✕</button></div>
            <div class="flex-1 space-y-6 overflow-y-auto p-6">
                <div><p class="text-xs font-mono text-slate-600" x-text="selectedCard.code"></p><h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 break-words" x-text="selectedCard.title"></h2><p class="mt-2 text-sm text-slate-600" x-text="selectedCard.project_name"></p></div>
                <dl class="grid grid-cols-2 gap-5 text-sm">
                    <div><dt class="text-slate-500">{{ __('Responsable') }}</dt><dd class="mt-1 font-semibold" x-text="selectedCard.assignee_name || 'Sin asignar'"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Prioridad') }}</dt><dd class="mt-1 font-semibold" x-text="selectedCard.priority_label"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Estado') }}</dt><dd class="mt-1 font-semibold" x-text="selectedCard.status_label"></dd></div>
                    <div><dt class="text-slate-500">{{ __('Vencimiento') }}</dt><dd class="mt-1 font-semibold" :class="selectedCard.overdue && 'text-rose-700'" x-text="selectedCard.due?.label || 'Sin fecha'"></dd></div>
                </dl>
                <div><div class="mb-2 flex justify-between text-sm font-semibold"><span>{{ __('Avance') }}</span><span x-text="selectedCard.progress + '%'"></span></div><div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-valuenow="selectedCard.progress" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Avance de actividad') }}"><div class="h-full rounded-full bg-emerald-600 transition-all" :style="'width:' + Math.min(100, Math.max(0, selectedCard.progress)) + '%'"></div></div></div>
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700" x-text="selectedCard.description || 'Esta actividad no tiene una descripción adicional.'"></p>
                <div x-show="selectedCard.blocking" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-semibold">{{ __('Bloqueo que requiere atención') }}</p><p class="mt-1" x-text="selectedCard.blocked_reason || 'Sin motivo registrado'"></p></div>
                <div x-show="selectedCard.can_update">
                    <label for="preview-state" class="mb-2 block text-sm font-semibold">{{ __('Cambiar estado') }}</label>
                    <select id="preview-state" x-model="previewState" :disabled="!!showSaving[selectedCard.id]" @change="const target = columns.find(c => c.slug === previewState); previewState = selectedCard.status_slug; if (target) { if (target.is_blocking && !selectedCard.blocked_reason) $dispatch('close'); moveViaMenu(selectedCard, target); }" class="w-full rounded-xl border-slate-300">
                        <template x-for="column in columns" :key="column.slug"><option :value="column.slug" :disabled="column.slug === 'completada' && selectedCard.status_slug !== 'completada'" x-text="column.name"></option></template>
                    </select>
                    <p class="mt-2 text-xs text-slate-500">{{ __('La finalización se realiza mediante la revisión de evidencias en el detalle completo.') }}</p>
                    <p x-show="showSaving[selectedCard.id]" role="status" class="mt-2 text-sm text-emerald-800">{{ __('Guardando cambio…') }}</p>
                    <p x-show="!toastOk" x-text="toast" role="alert" class="mt-2 text-sm text-rose-700"></p>
                    <button x-show="!toastOk && retryMove" type="button" class="workspace-button mt-2" @click="retryAction()">{{ __('Reintentar cambio') }}</button>
                </div>
            </div>
            <div class="border-t border-slate-200 bg-slate-50 p-5"><a :href="@js(route('tasks.show', ['task' => '__ID__'])).replace('__ID__', selectedCard.id)" class="workspace-primary w-full">{{ __('Abrir actividad y evidencias') }} →</a></div>
        </div>
    </template>
</x-ui.modal>
