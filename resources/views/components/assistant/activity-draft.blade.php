<div x-show="message.action?.type === 'create_activity'" class="mt-3 border-t border-slate-200 pt-3">
    <template x-if="message.action?.type === 'create_activity' && !message.action.created">
        <form @submit.prevent="createActivity(message)" class="space-y-3" aria-label="Borrador de actividad">
            <fieldset :disabled="message.action.submitting" class="space-y-3">
                <label class="block text-xs font-semibold">Proyecto<select x-model="message.action.draft.project_id" @change="message.action.draft.assignee_id = ''" required class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Selecciona un proyecto</option><template x-for="p in message.action.projects" :key="p.id"><option :value="p.id" x-text="p.name"></option></template></select></label>
                <label class="block text-xs font-semibold">Actividad<input x-model="message.action.draft.title" required maxlength="200" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                <label class="block text-xs font-semibold">Responsable<select x-model="message.action.draft.assignee_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Selecciona un miembro activo</option><template x-for="e in draftProject(message.action)?.members || []" :key="e.id"><option :value="e.id" x-text="e.name"></option></template></select></label>
                <label class="block text-xs font-semibold">Prioridad<select x-model="message.action.draft.priority" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="low">Baja</option><option value="medium">Media</option><option value="high">Alta</option><option value="critical">Crítica</option></select></label>
                <div class="grid grid-cols-2 gap-2"><label class="block text-xs font-semibold">Inicio<input type="date" x-model="message.action.draft.start" class="mt-1 w-full min-w-0 rounded-lg border-slate-300 text-sm"></label><label class="block text-xs font-semibold">Vencimiento<input type="date" x-model="message.action.draft.due" :min="message.action.draft.start" class="mt-1 w-full min-w-0 rounded-lg border-slate-300 text-sm"></label></div>
                <label class="block text-xs font-semibold">Descripción<textarea x-model="message.action.draft.description" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea></label>
                <p class="text-xs font-semibold">Tareas de la actividad</p>
                <template x-for="(row, rowIndex) in message.action.draft.tasks" :key="rowIndex"><div class="flex gap-2"><input x-model="row.title" required maxlength="200" :aria-label="'Título de tarea ' + (rowIndex + 1)" placeholder="Título de la tarea" class="w-full min-w-0 rounded-lg border-slate-300 text-sm"><button type="button" @click="message.action.draft.tasks.splice(rowIndex,1)" :disabled="message.action.draft.tasks.length === 1" aria-label="Quitar tarea" class="px-2">×</button></div></template>
                <button type="button" @click="message.action.draft.tasks.push({ title: '', description: '' })" class="text-xs font-semibold text-emerald-800">+ Añadir tarea</button>
                <p x-show="message.action.error" x-text="message.action.error" role="alert" class="text-xs text-rose-700"></p>
                <button type="submit" class="workspace-primary w-full" x-text="message.action.submitting ? 'Guardando…' : 'Crear actividad'"></button>
                <button type="button" @click="message.action = null" class="w-full text-xs text-slate-600">Descartar borrador</button>
            </fieldset>
        </form>
    </template>
    <template x-if="message.action?.created"><a :href="message.action.createdUrl" class="workspace-button">Ver actividad creada →</a></template>
</div>
