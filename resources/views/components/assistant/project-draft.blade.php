<template x-if="message.action?.type === 'create_project'">
    <div class="mt-3 border-t border-slate-200 pt-3">
        <template x-if="!message.action.created">
            <div class="space-y-3">
                <p class="text-xs text-slate-600">Completa los campos en la ventana de edición. El proyecto se guardará cuando pulses Crear proyecto.</p>
                <p class="text-sm font-semibold break-words" x-text="message.action.draft.name || 'Proyecto sin nombre'"></p>
                <button type="button" class="workspace-primary w-full" @click="editProject(message)" :disabled="!!message.action.submitting">Completar datos del proyecto</button>
                <button type="button" @click="message.action = null" class="w-full min-h-[36px] text-xs text-slate-600" :disabled="!!message.action.submitting">Descartar borrador</button>
            </div>
        </template>
        <template x-if="message.action.created"><a :href="message.action.createdUrl" class="workspace-button">Ver proyecto creado →</a></template>
    </div>
</template>
