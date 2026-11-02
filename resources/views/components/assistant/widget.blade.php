@props(['embedded' => false, 'projects' => collect()])
@php
    $routeProject = request()->route('project');
    $routeTask = request()->route('task');
    $contextProject = $routeProject instanceof \App\Models\Project ? $routeProject : ($routeTask instanceof \App\Models\Task ? $routeTask->project : null);
    $contextTask = $routeTask instanceof \App\Models\Task ? $routeTask : null;
    $suggestions = ['Analiza todos los proyectos', '¿Cuántos documentos hay?', auth()->user()->can('create', \App\Models\Task::class) ? 'Crea una actividad' : '¿Qué actividades vencen esta semana?'];
    $boot = [
        'embedded' => $embedded, 'userId' => auth()->id(), 'projectId' => $contextProject?->id, 'taskId' => $contextTask?->id,
        'projects' => $projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->nombre, 'tasks' => $p->tasks->map(fn ($t) => ['id' => $t->id, 'title' => $t->titulo])->values()])->values(),
        'sendUrl' => route('rag-query.store'), 'historyUrl' => route('assistant.conversations'),
        'voiceAvailable' => app(\App\Services\Assistant\GroqGateway::class)->enabled() && filled(config('rag.completion.groq.api_key')),
        'conversationUrl' => route('rag-query.conversations.show', ['conversation' => '__ID__']),
        'clearUrl' => route('assistant.conversations.clear'), 'transcribeUrl' => route('assistant.transcribe'), 'sourceUrl' => route('rag-documents.source', ['ragDocument' => '__ID__']),
    ];
@endphp
@if($embedded || !request()->routeIs('rag-query.index'))
<div x-data="assistantClient(@js($boot))" class="{{ $embedded ? 'assistant-host' : 'assistant-floating' }}" :class="{ 'assistant-dock-left': !embedded && dockLeft, 'is-dragging': !!drag, 'assistant-floating': isFloating }" :style="floatingStyle" @keydown.escape.stop="if (isFloating && open) { open = false; stopAll(); $refs.launcher.focus(); }">
    <button x-show="isFloating" @if($embedded) x-cloak @endif :style="launcherStyle" type="button" x-ref="launcher" @click="toggle()" @pointerdown="beginDrag($event)" @pointermove="dragTo($event)" @pointerup="finishDrag($event)" @pointercancel="cancelDrag($event)" @keydown.left.prevent="nudgePosition(-24,0)" @keydown.right.prevent="nudgePosition(24,0)" @keydown.up.prevent="nudgePosition(0,-24)" @keydown.down.prevent="nudgePosition(0,24)" class="assistant-launcher" :class="{ 'is-listening': recording, 'is-working': loading || transcribing, 'is-open': open }" title="Abre el asistente. Arrastra para moverlo; también puedes usar las flechas del teclado." :aria-expanded="open" aria-controls="{{ $embedded ? 'main' : 'floating' }}-assistant-panel" aria-label="{{ __('Abrir o minimizar asistente con voz') }}">
            <x-assistant.avatar />
            <span class="assistant-presence" aria-hidden="true"></span><span class="sr-only">{{ __('Asistente') }}</span>
        </button>
    <div x-show="embedded && detached" x-cloak class="assistant-detached-placeholder"><x-assistant.avatar /><p>Tu asistente está en una ventana flotante.</p><button type="button" class="workspace-button" @click="toggleFloating()">Volver a pantalla completa</button></div>
    <section id="{{ $embedded ? 'main' : 'floating' }}-assistant-panel" x-show="open" @if(!$embedded) x-cloak @endif class="assistant-panel {{ $embedded ? 'assistant-panel-embedded' : '' }}" aria-label="{{ __('Asistente del sistema') }}" :style="floatingPanelStyle">
        <div class="assistant-header">
            <button x-show="isFloating" type="button" class="assistant-drag-handle" @pointerdown="beginDrag($event)" @pointermove="dragTo($event)" @pointerup="finishDrag($event)" @pointercancel="cancelDrag($event)" @keydown.left.prevent="nudgePosition(-24,0)" @keydown.right.prevent="nudgePosition(24,0)" @keydown.up.prevent="nudgePosition(0,-24)" @keydown.down.prevent="nudgePosition(0,24)" aria-label="Mover asistente: arrastra o usa las flechas del teclado" title="Arrastrar para mover"><svg viewBox="0 0 16 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="6" r="1.5"/><circle cx="11" cy="6" r="1.5"/><circle cx="5" cy="12" r="1.5"/><circle cx="11" cy="12" r="1.5"/><circle cx="5" cy="18" r="1.5"/><circle cx="11" cy="18" r="1.5"/></svg></button>
            <div class="assistant-identity"><span class="assistant-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="6" width="16" height="14" rx="5"/><path d="M12 3v3M8 15h8"/><circle cx="8.5" cy="11" r="1" fill="currentColor"/><circle cx="15.5" cy="11" r="1" fill="currentColor"/></svg></span><div><h2 class="text-sm font-bold text-slate-900">{{ __('Tu asistente') }}</h2><p role="status" class="mt-0.5 text-xs text-emerald-800" x-text="status"></p></div></div>
            <div class="flex items-center gap-1">
                @if($embedded)<button type="button" class="assistant-icon-button assistant-mode-toggle" @click="toggleFloating()" :aria-label="detached ? 'Volver a pantalla completa' : 'Usar ventana flotante'" :title="detached ? 'Pantalla completa' : 'Ventana flotante'"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><rect x="10" y="10" width="11" height="11" rx="3" fill="currentColor" fill-opacity=".15"/></svg><span class="hidden sm:inline" x-text="detached ? 'Ampliar' : 'Ventana'"></span></button>@endif
                <button type="button" class="assistant-icon-button" @click="historyOpen = !historyOpen" :aria-expanded="historyOpen" aria-label="Mostrar u ocultar conversaciones" title="Conversaciones"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="4"/><path d="M9 4v16"/></svg></button>
                <button type="button" class="assistant-icon-button" @click="settingsOpen = !settingsOpen" :aria-expanded="settingsOpen" aria-label="Opciones del asistente" title="Opciones">⋯</button>
                @unless($embedded)<a class="assistant-icon-button" :href="@js(route('rag-query.index')) + (conversationId ? '?conversation=' + conversationId : '')" aria-label="Abrir asistente completo" title="Ampliar">↗</a><button type="button" class="assistant-icon-button" @click="toggle(); $refs.launcher.focus()" aria-label="Minimizar asistente" title="Minimizar">−</button>@endunless
            </div>
        </div>
        <div x-show="settingsOpen" x-cloak class="assistant-options">
            <button type="button" @click="historyOpen = !historyOpen; settingsOpen = false" :aria-expanded="historyOpen">Historial</button>
            <button type="button" @click="startNew(); settingsOpen = false" :disabled="loading || recording || transcribing">Nueva conversación</button>
            <button type="button" @click="toggleVoice()" :aria-pressed="voiceEnabled" x-text="voiceEnabled ? 'Lectura automática: sí' : 'Lectura automática: no'"></button>
            <button type="button" @click="startAudioRecording(); settingsOpen = false" :disabled="loading || recording || transcribing">Grabar y transcribir</button>
            <button x-show="isFloating" type="button" @click="moveDock(); settingsOpen = false">Mover al otro lado</button><button x-show="isFloating" type="button" @click="resetPosition(); settingsOpen = false">Restablecer posición</button>
            <p>Al dictar se usa el reconocimiento de tu navegador. La alternativa de grabación envía hasta 30 segundos de audio a Groq para transcribirlo; no se guarda en el sistema.</p>
        </div>
        <div class="assistant-body">
            <aside x-show="historyOpen" x-cloak class="assistant-history flex flex-col" aria-label="{{ __('Conversaciones guardadas') }}">
                <div class="shrink-0 border-b border-slate-100 p-3">
                    <button type="button" @click="startNew()" :disabled="loading || recording || transcribing" class="workspace-primary w-full !min-h-[38px] text-xs">+ {{ __('Nueva conversación') }}</button>
                </div>
                <p x-show="!conversations.length" class="p-4 text-sm text-slate-600">{{ __('Todavía no hay conversaciones guardadas.') }}</p>
                <div class="min-h-0 flex-1 overflow-y-auto">
                    <template x-for="conversation in conversations" :key="conversation.id">
                        <div class="group flex items-center border-b border-slate-100 hover:bg-emerald-50" :class="conversationId === conversation.id && 'bg-emerald-50 text-emerald-900'">
                            <button type="button" @click="loadConversation(conversation.id)" :disabled="loading || recording || transcribing" :aria-current="conversationId === conversation.id ? 'true' : 'false'" class="min-w-0 flex-1 truncate p-3 text-left text-sm" x-text="conversation.title"></button>
                            <button type="button" @click.stop="deleteConversation(conversation)" :disabled="loading || recording || transcribing" :aria-label="'Eliminar conversación: ' + conversation.title" title="Eliminar conversación" class="mr-2 shrink-0 rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 focus:opacity-100 lg:opacity-0 lg:group-hover:opacity-100">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48.1 48.1 0 00-3.48-.4m-12 .56c.34-.06.68-.12 1.02-.17m0 0a48.11 48.11 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <div x-show="conversations.length" class="shrink-0 border-t border-slate-100 p-2">
                    <button type="button" @click="clearHistory()" :disabled="loading || recording || transcribing" class="w-full rounded-lg py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('Borrar todo el historial') }}</button>
                </div>
            </aside>
            <div class="assistant-chat">
                @if($embedded)
                    <div class="shrink-0 border-b border-slate-100 px-4 py-2">
                        <button type="button" @click="contextOpen = !contextOpen" :aria-expanded="contextOpen" class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                            <span class="shrink-0 text-slate-500">{{ __('Contexto') }}:</span>
                            <span class="truncate" x-text="(projects.find(p => String(p.id) === String(projectId))?.name || @js(__('Todos los proyectos accesibles'))) + (taskId ? ' · ' + (availableTasks.find(t => String(t.id) === String(taskId))?.title || '') : '')"></span>
                            <svg class="h-3 w-3 shrink-0 transition-transform" :class="contextOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="contextOpen" x-cloak class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label class="text-xs font-medium text-slate-600">{{ __('Proyecto (opcional)') }}<select x-model="projectId" @change="changeContext()" :disabled="loading || recording || transcribing" class="mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">{{ __('Todos los proyectos accesibles') }}</option><template x-for="project in projects" :key="project.id"><option :value="project.id" x-text="project.name"></option></template></select></label>
                            <label class="text-xs font-medium text-slate-600">{{ __('Actividad reciente (máx. 100, opcional)') }}<select x-model="taskId" @change="startNew()" :disabled="!projectId || loading || recording || transcribing" class="mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">{{ __('Sin actividad específica') }}</option><template x-for="task in availableTasks" :key="task.id"><option :value="task.id" x-text="task.title"></option></template></select></label>
                        </div>
                    </div>
                @elseif($contextProject)
                    <p class="shrink-0 border-b border-emerald-100 bg-emerald-50 px-4 py-2 text-xs text-emerald-900">{{ __('Contexto') }}: {{ $contextProject->nombre }}@if($contextTask) · {{ $contextTask->codigo }}@endif</p>
                @endif
                <div class="assistant-messages" x-ref="messages" role="log" aria-label="{{ __('Mensajes') }}" :aria-busy="loading">
                    <div x-show="messages.length === 0" class="assistant-welcome mx-auto max-w-lg text-center">
                        <span class="assistant-welcome-symbol"><x-assistant.avatar class="assistant-avatar-large" /></span><p class="workspace-eyebrow">{{ __('Tu copiloto de trabajo') }}</p><h3 class="text-xl font-bold text-slate-900">{{ __('¿Por dónde empezamos?') }}</h3><p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('Pregunta por tus proyectos, actividades o documentos.') }}</p>
                        <div class="assistant-suggestions mt-4 grid gap-2">
                            @foreach($suggestions as $suggestion)<button type="button" class="workspace-button justify-start text-left" @click="question = @js($suggestion); $refs.question.focus()"><span class="assistant-suggestion-icon" aria-hidden="true">{{ ['↗', '▤', '+'][$loop->index] }}</span><span>{{ __($suggestion) }}</span><span class="assistant-suggestion-arrow" aria-hidden="true">↗</span></button>@endforeach
                        </div>
                    </div>
                    <template x-for="(message,index) in messages" :key="index"><div class="mb-4 flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'"><div class="max-w-[92%] rounded-2xl px-4 py-3 text-sm leading-relaxed" :class="message.role === 'user' ? 'assistant-message-user' : 'assistant-message-answer'">
                        <p class="whitespace-pre-wrap break-words" x-text="message.text"></p>
                        @include('components.assistant.activity-draft')
                        @include('components.assistant.project-draft')
                        <div x-show="message.sources?.length" class="mt-3 flex flex-wrap gap-2 border-t border-slate-200 pt-3"><template x-for="(source,sourceIndex) in message.sources || []" :key="sourceIndex"><a :href="sourceUrl(source)" target="_blank" rel="noopener" class="assistant-source inline-flex min-h-[36px] items-center rounded-lg border border-emerald-200 bg-white px-2 py-1 text-xs font-semibold text-emerald-900" x-text="source.title || source.document_title || 'Consultar fuente'"></a></template></div>
                        <div x-show="message.role === 'assistant'" class="assistant-answer-actions mt-2">
                            <button type="button" @click="copyAnswer(message.text, index)" :aria-label="copiedMessage === index ? 'Respuesta copiada' : 'Copiar respuesta'" x-text="copiedMessage === index ? 'Copiado ✓' : 'Copiar'" aria-live="polite"></button>
                            <button type="button" @click="speak(message.text)">{{ __('Escuchar respuesta') }}</button>
                        </div>
                    </div></div></template>
                    <p x-show="loading" role="status" class="text-sm text-emerald-800">{{ __('Consultando la información de tu acceso…') }}</p>
                </div>
                <div class="assistant-composer">
                    <p x-show="error" x-text="error" role="alert" class="mb-2 text-sm text-rose-700"></p>
                    <p x-show="dictationHint || transcribing" x-text="transcribing ? 'Convirtiendo tu voz en texto…' : dictationHint" role="status" class="assistant-dictation-status"></p>
                    <form @submit.prevent="send()" class="assistant-compose-form">
                        <label class="sr-only" for="{{ $embedded ? 'main' : 'floating' }}-question">{{ __('Tu pregunta al asistente') }}</label><textarea id="{{ $embedded ? 'main' : 'floating' }}-question" x-ref="question" x-model="question" rows="1" maxlength="2000" :disabled="loading || transcribing" :readonly="recording" @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }" placeholder="{{ __('Escribe o dicta tu pregunta…') }}" class="min-w-0 flex-1 resize-none rounded-xl border-slate-300 text-sm"></textarea>
                        <div class="assistant-compose-actions"><button type="button" class="assistant-icon-button assistant-mic" @click="recording ? finishRecording() : startRecording()" :disabled="loading || transcribing" :aria-label="recording ? 'Detener dictado' : 'Dictar pregunta'" title="Dictar o detener" :aria-pressed="recording" :class="recording && 'bg-rose-50 text-rose-700'" ><svg x-show="!recording" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0014 0v-2M12 19v3m-4 0h8"/></svg><span x-show="recording" aria-hidden="true">■</span></button>
                        <button type="submit" class="assistant-send" aria-label="Enviar pregunta" title="Enviar" :disabled="!question.trim() || loading || recording || transcribing"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21 3-7 18-4-8-7-4 18-6ZM10 13l11-10" stroke-linejoin="round"/></svg></button>
                        </div>
                    </form>
                    <button x-show="speaking" type="button" @click="stopSpeaking()" class="mt-1 text-xs font-semibold text-rose-700">{{ __('Detener lectura') }}</button>
                </div>
            </div>
        </div>
    </section>
    @include('components.assistant.project-editor')
</div>
@endif
