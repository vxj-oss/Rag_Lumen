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
        'transcribeUrl' => route('assistant.transcribe'), 'sourceUrl' => route('rag-documents.source', ['ragDocument' => '__ID__']),
    ];
@endphp
@if($embedded || !request()->routeIs('rag-query.index'))
<div x-data="assistantClient(@js($boot))" class="{{ $embedded ? 'assistant-host' : 'assistant-floating' }}" :class="!embedded && dockLeft && 'assistant-dock-left'" @keydown.escape.stop="if (!embedded && open) { open = false; stopAll(); $refs.launcher.focus(); }">
    @unless($embedded)
        <button type="button" x-ref="launcher" @click="toggle()" class="assistant-launcher" :class="{ 'is-listening': recording, 'is-working': loading || transcribing, 'is-open': open }" title="Asistente: pregunta por texto o voz" :aria-expanded="open" aria-controls="floating-assistant-panel" aria-label="{{ __('Abrir o minimizar asistente con voz') }}">
            <svg viewBox="0 0 64 64" class="assistant-robot" fill="none" aria-hidden="true"><path d="M32 7v7" stroke="currentColor" stroke-width="3"/><circle cx="32" cy="6" r="3" fill="currentColor"/><rect x="12" y="17" width="40" height="33" rx="12" fill="currentColor" fill-opacity=".15" stroke="currentColor" stroke-width="3"/><g class="assistant-eyes"><rect x="20" y="27" width="8" height="9" rx="4" fill="currentColor"/><rect x="36" y="27" width="8" height="9" rx="4" fill="currentColor"/></g><path d="M24 41h16M6 27v13m52-13v13" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            <span class="assistant-presence" aria-hidden="true"></span><span class="sr-only">{{ __('Asistente') }}</span>
        </button>
    @endunless
    <section id="{{ $embedded ? 'main' : 'floating' }}-assistant-panel" x-show="open" @if(!$embedded) x-cloak @endif class="assistant-panel {{ $embedded ? 'assistant-panel-embedded' : '' }}" aria-label="{{ __('Asistente del sistema') }}">
        <div class="assistant-header">
            <div><h2 class="text-sm font-bold text-slate-900">{{ __('Tu asistente') }}</h2><p role="status" class="mt-0.5 text-xs text-emerald-800" x-text="status"></p></div>
            <div class="flex items-center gap-1">
                <button type="button" class="assistant-icon-button" @click="settingsOpen = !settingsOpen" :aria-expanded="settingsOpen" aria-label="Opciones del asistente" title="Opciones">⋯</button>
                @unless($embedded)<a class="assistant-icon-button" :href="@js(route('rag-query.index')) + (conversationId ? '?conversation=' + conversationId : '')" aria-label="Abrir asistente completo" title="Ampliar">↗</a><button type="button" class="assistant-icon-button" @click="toggle(); $refs.launcher.focus()" aria-label="Minimizar asistente" title="Minimizar">−</button>@endunless
            </div>
        </div>
        <div x-show="settingsOpen" x-cloak class="assistant-options">
            <button type="button" @click="historyOpen = !historyOpen; settingsOpen = false" :aria-expanded="historyOpen">Historial</button>
            <button type="button" @click="startNew(); settingsOpen = false" :disabled="loading || recording || transcribing">Nueva conversación</button>
            <button type="button" @click="toggleVoice()" :aria-pressed="voiceEnabled" x-text="voiceEnabled ? 'Lectura automática: sí' : 'Lectura automática: no'"></button>
            <button type="button" @click="startAudioRecording(); settingsOpen = false" :disabled="loading || recording || transcribing">Grabar y transcribir</button>
            @unless($embedded)<button type="button" @click="moveDock(); settingsOpen = false">Mover al otro lado</button>@endunless
            <p>Al dictar se usa el reconocimiento de tu navegador. La alternativa de grabación envía hasta 30 segundos de audio a Groq para transcribirlo; no se guarda en el sistema.</p>
        </div>
        <div class="assistant-body">
            <aside x-show="historyOpen" x-cloak class="assistant-history" aria-label="{{ __('Conversaciones guardadas') }}">
                <p x-show="!conversations.length" class="p-4 text-sm text-slate-600">{{ __('Todavía no hay conversaciones guardadas.') }}</p>
                <template x-for="conversation in conversations" :key="conversation.id"><button type="button" @click="loadConversation(conversation.id)" :disabled="loading || recording || transcribing" :aria-current="conversationId === conversation.id ? 'true' : 'false'" class="block w-full border-b border-slate-100 p-4 text-left text-sm hover:bg-emerald-50" :class="conversationId === conversation.id && 'bg-emerald-50 text-emerald-900'" x-text="conversation.title"></button></template>
            </aside>
            <div class="assistant-chat">
                @if($embedded)
                    <div class="grid shrink-0 grid-cols-1 gap-2 border-b border-slate-100 bg-slate-50 p-3 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">{{ __('Proyecto (opcional)') }}<select x-model="projectId" @change="changeContext()" :disabled="loading || recording || transcribing" class="mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">{{ __('Todos los proyectos accesibles') }}</option><template x-for="project in projects" :key="project.id"><option :value="project.id" x-text="project.name"></option></template></select></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('Actividad reciente (máx. 100, opcional)') }}<select x-model="taskId" @change="startNew()" :disabled="!projectId || loading || recording || transcribing" class="mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">{{ __('Sin actividad específica') }}</option><template x-for="task in availableTasks" :key="task.id"><option :value="task.id" x-text="task.title"></option></template></select></label>
                    </div>
                @elseif($contextProject)
                    <p class="shrink-0 border-b border-emerald-100 bg-emerald-50 px-4 py-2 text-xs text-emerald-900">{{ __('Contexto') }}: {{ $contextProject->nombre }}@if($contextTask) · {{ $contextTask->codigo }}@endif</p>
                @endif
                <div class="assistant-messages" x-ref="messages" role="log" aria-label="{{ __('Mensajes') }}" :aria-busy="loading">
                    <div x-show="messages.length === 0" class="assistant-welcome mx-auto max-w-lg text-center">
                        <p class="workspace-eyebrow">{{ __('Aquí para ayudarte') }}</p><h3 class="text-xl font-bold text-slate-900">{{ __('¿En qué te ayudo hoy?') }}</h3><p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('Pregunta por tus proyectos, actividades o documentos.') }}</p>
                        <div class="assistant-suggestions mt-4 grid gap-2">
                            @foreach($suggestions as $suggestion)<button type="button" class="workspace-button justify-start text-left" @click="question = @js($suggestion); $refs.question.focus()">{{ __($suggestion) }} →</button>@endforeach
                        </div>
                    </div>
                    <template x-for="(message,index) in messages" :key="index"><div class="mb-4 flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'"><div class="max-w-[92%] rounded-2xl px-4 py-3 text-sm leading-relaxed" :class="message.role === 'user' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-800'">
                        <p class="whitespace-pre-wrap break-words" x-text="message.text"></p>
                        @include('components.assistant.activity-draft')
                        @include('components.assistant.project-draft')
                        <div x-show="message.sources?.length" class="mt-3 flex flex-wrap gap-2 border-t border-slate-200 pt-3"><template x-for="(source,sourceIndex) in message.sources || []" :key="sourceIndex"><a :href="sourceUrl(source)" target="_blank" rel="noopener" class="inline-flex min-h-[36px] items-center rounded-lg border border-emerald-200 bg-white px-2 py-1 text-xs font-semibold text-emerald-900" x-text="source.title || source.document_title || 'Consultar fuente'"></a></template></div>
                        <button x-show="message.role === 'assistant'" type="button" @click="speak(message.text)" class="mt-2 min-h-[36px] text-xs font-semibold text-emerald-900">{{ __('Escuchar respuesta') }}</button>
                    </div></div></template>
                    <p x-show="loading" role="status" class="text-sm text-emerald-800">{{ __('Consultando la información de tu acceso…') }}</p>
                </div>
                <div class="assistant-composer">
                    <p x-show="error" x-text="error" role="alert" class="mb-2 text-sm text-rose-700"></p>
                    <p x-show="dictationHint || transcribing" x-text="transcribing ? 'Convirtiendo tu voz en texto…' : dictationHint" role="status" class="assistant-dictation-status"></p>
                    <form @submit.prevent="send()" class="flex items-end gap-2">
                        <label class="sr-only" for="{{ $embedded ? 'main' : 'floating' }}-question">{{ __('Tu pregunta al asistente') }}</label><textarea id="{{ $embedded ? 'main' : 'floating' }}-question" x-ref="question" x-model="question" rows="1" maxlength="2000" :disabled="loading || transcribing" :readonly="recording" @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }" placeholder="{{ __('Escribe o dicta tu pregunta…') }}" class="min-w-0 flex-1 resize-none rounded-xl border-slate-300 text-sm"></textarea>
                        <button type="button" class="assistant-icon-button assistant-mic" @click="recording ? finishRecording() : startRecording()" :disabled="loading || transcribing" :aria-label="recording ? 'Detener dictado' : 'Dictar pregunta'" title="Dictar o detener" :aria-pressed="recording" :class="recording && 'bg-rose-50 text-rose-700'" ><svg x-show="!recording" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0014 0v-2M12 19v3m-4 0h8"/></svg><span x-show="recording" aria-hidden="true">■</span></button>
                        <button type="submit" class="assistant-send" aria-label="Enviar pregunta" title="Enviar" :disabled="!question.trim() || loading || recording || transcribing"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 19V5m-6 6 6-6 6 6"/></svg></button>
                    </form>
                    <button x-show="speaking" type="button" @click="stopSpeaking()" class="mt-1 text-xs font-semibold text-rose-700">{{ __('Detener lectura') }}</button>
                </div>
            </div>
        </div>
    </section>
</div>
@endif
