<div x-show="viewMode === 'list' && !loading && visibleCards().length" x-cloak class="workspace-activity-list overflow-x-auto rounded-2xl border border-slate-200 bg-white">
    <table class="workspace-activity-table w-full text-left text-sm">
        <caption class="sr-only">{{ __('Actividades de la vista seleccionada') }}</caption>
        <colgroup><col style="width:32%"><col style="width:15%"><col style="width:15%"><col style="width:17%"><col style="width:11%"><col style="width:10%"></colgroup>
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide"><tr><th scope="col">{{ __('Actividad') }}</th><th scope="col">{{ __('Responsable') }}</th><th scope="col">{{ __('Estado') }}</th><th scope="col">{{ __('Vencimiento') }}</th><th scope="col">{{ __('Avance') }}</th><th scope="col">{{ __('Acciones') }}</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            <template x-for="card in pageCards()" :key="card.id">
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4"><button type="button" @click="openDetail(card)" class="text-left font-semibold leading-snug text-slate-900 hover:text-emerald-800" x-text="card.title"></button><p class="mt-1 text-xs font-mono text-slate-500" x-text="card.code"></p><p class="mt-1 text-xs leading-relaxed text-slate-500" x-text="card.project_name"></p></td>
                    <td class="px-5 py-4" x-text="card.assignee_name || 'Sin asignar'"></td>
                    <td class="px-5 py-4"><span class="inline-flex max-w-full items-center gap-2 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold"><span class="h-2 w-2 shrink-0 rounded-full" :class="dotClass(columns.find(c => c.slug === card.status_slug)?.color)"></span><span x-text="card.status_label"></span></span></td>
                    <td class="px-5 py-4" :class="card.overdue ? 'font-semibold text-rose-700' : 'text-slate-600'" x-text="card.due?.label || 'Sin fecha'"></td>
                    <td class="px-5 py-4"><span class="font-semibold tabular-nums" x-text="card.progress + '%'"></span><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-valuenow="card.progress" aria-valuemin="0" aria-valuemax="100" aria-label="Avance de actividad"><div class="h-full rounded-full bg-emerald-600" :style="'width:' + Math.min(100, Math.max(0, card.progress)) + '%'"></div></div></td>
                    <td class="px-5 py-4"><button type="button" class="workspace-button" @click="openDetail(card)">{{ __('Revisar') }}</button></td>
                </tr>
            </template>
        </tbody>
    </table>
</div>

