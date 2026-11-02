<template x-teleport="body">
    <x-ui.modal :name="$embedded ? 'main-project-draft' : 'floating-project-draft'" :label="__('Completar proyecto')" max-width="3xl" focusable>
        <template x-for="message in (projectDraftMessage?.action?.type === 'create_project' && !projectDraftMessage.action.created ? [projectDraftMessage] : [])" :key="projectEditorName">
            <form @submit.prevent="createProject(message)" class="assistant-project-editor" aria-label="Borrador de proyecto">
                <div class="assistant-project-editor-header">
                    <div><h2 class="workspace-section-title">Completar proyecto</h2><p class="mt-1 text-xs text-slate-600">Los campos con * son obligatorios. Puedes volver al chat y reabrir este borrador.</p></div>
                    <button type="button" @click="$dispatch('close')" class="workspace-button shrink-0" aria-label="Cerrar editor de proyecto" :disabled="!!message.action.submitting">✕</button>
                </div>
                <div class="assistant-project-editor-fields">
                    <fieldset :disabled="!!message.action.submitting" class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
                        <legend class="sr-only">Datos del proyecto</legend>
                        <label class="block text-sm font-medium sm:col-span-2">Nombre *<input x-model="message.action.draft.name" required maxlength="150" class="mt-1 w-full text-sm" autocomplete="off"></label>
                        <label class="block text-sm font-medium">Código *<input x-model="message.action.draft.code" required maxlength="30" class="mt-1 w-full text-sm" autocomplete="off"></label>
                        <label class="block text-sm font-medium">Tipo *<select x-model="message.action.draft.type" required class="mt-1 w-full text-sm"><template x-for="t in message.action.types" :key="t.value"><option :value="t.value" x-text="t.label"></option></template></select></label>
                        <label class="block text-sm font-medium">Cliente<select x-model="message.action.draft.client_id" class="mt-1 w-full text-sm"><option value="">Sin cliente</option><template x-for="c in message.action.clients" :key="c.id"><option :value="c.id" x-text="c.nombre"></option></template></select></label>
                        <label class="block text-sm font-medium">Responsable<select x-model="message.action.draft.responsible_id" class="mt-1 w-full text-sm"><option value="">Sin asignar</option><template x-for="e in message.action.employees" :key="e.id"><option :value="e.id" x-text="e.name"></option></template></select></label>
                        <label class="block text-sm font-medium">Gerente<select x-model="message.action.draft.manager_id" class="mt-1 w-full text-sm"><option value="">Sin asignar</option><template x-for="e in message.action.employees" :key="e.id"><option :value="e.id" x-text="e.name"></option></template></select></label>
                        <label class="block text-sm font-medium">Prioridad<select x-model="message.action.draft.priority" class="mt-1 w-full text-sm"><option value="low">Baja</option><option value="medium">Media</option><option value="high">Alta</option><option value="critical">Crítica</option></select></label>
                        <fieldset class="min-w-0 sm:col-span-2"><legend class="text-sm font-medium">Áreas</legend><div class="mt-1 flex flex-wrap gap-x-4 gap-y-1"><template x-for="a in message.action.areas" :key="a.id"><label class="inline-flex min-h-[44px] items-center gap-2 text-sm"><input type="checkbox" :value="String(a.id)" x-model="message.action.draft.area_ids" class="rounded border-slate-300"><span x-text="a.nombre"></span></label></template><p x-show="!message.action.areas?.length" class="py-2 text-xs text-slate-500">No hay áreas activas disponibles.</p></div></fieldset>
                        <label class="block min-w-0 text-sm font-medium">Fecha de inicio *<input type="date" x-model="message.action.draft.start" required class="mt-1 w-full min-w-0 text-sm"></label>
                        <label class="block min-w-0 text-sm font-medium">Entrega estimada *<input type="date" x-model="message.action.draft.end" :min="message.action.draft.start" required class="mt-1 w-full min-w-0 text-sm"></label>
                        <label class="block text-sm font-medium">Presupuesto<input type="number" min="0" step="0.01" x-model="message.action.draft.budget" class="mt-1 w-full text-sm" placeholder="Opcional"></label>
                        <label class="block text-sm font-medium sm:col-span-2">Descripción<textarea x-model="message.action.draft.description" rows="3" maxlength="5000" class="mt-1 w-full text-sm" placeholder="Describe el alcance del proyecto"></textarea></label>
                    </fieldset>
                </div>
                <div class="assistant-project-editor-footer">
                    <p x-show="message.action.error" x-text="message.action.error" role="alert" class="w-full text-sm text-rose-700"></p>
                    <button type="button" @click="$dispatch('close')" class="workspace-button" :disabled="!!message.action.submitting">Volver al chat</button>
                    <button type="submit" class="workspace-primary" :disabled="!!message.action.submitting" x-text="message.action.submitting ? 'Guardando…' : 'Crear proyecto'"></button>
                </div>
            </form>
        </template>
    </x-ui.modal>
</template>
