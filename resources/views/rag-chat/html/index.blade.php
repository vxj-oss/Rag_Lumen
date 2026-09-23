<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Asistente RAG') }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('Pregunta sobre tus proyectos y documentos; el asistente responde citando las fuentes.') }}</p>
        </div>
    </x-slot>

    <div class="flex h-[calc(100dvh-5.75rem)] py-4 gap-4 px-4 sm:px-6 lg:px-8"
         x-data="{
            projects: @js($projects->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->nombre,
                'tasks' => $p->tasks->map(fn ($t) => ['id' => $t->id, 'title' => $t->titulo])->values(),
            ])),
            conversations: @js($conversations->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->titulo,
                'project_id' => $c->proyecto_id,
                'task_id' => $c->tarea_id,
            ])),
            conversationId: null,
            sidebarOpen: false,
            projectId: '',
            taskId: '',
            question: '',
            loading: false,
            error: null,
            messages: [],
            get tasksForSelectedProject() {
                const project = this.projects.find(p => String(p.id) === String(this.projectId));
                return project ? project.tasks : [];
            },
            newConversation() {
                this.conversationId = null;
                this.projectId = '';
                this.taskId = '';
                this.messages = [];
                this.error = null;
                this.sidebarOpen = false;
            },
            async loadConversation(id) {
                this.sidebarOpen = false;
                this.error = null;
                try {
                    const res = await fetch(`/rag/conversations/${id}`, { headers: { 'Accept': 'application/json' } });
                    if (! res.ok) return;
                    const data = await res.json();
                    this.conversationId = data.conversation.id;
                    this.projectId = data.conversation.project_id ?? '';
                    this.taskId = data.conversation.task_id ?? '';
                    this.messages = data.messages;
                    this.$nextTick(() => this.$refs.messagesEnd?.scrollIntoView());
                } catch (e) {
                    this.error = 'No se pudo cargar la conversación.';
                }
            },
            async deleteConversation(id) {
                if (! confirm(@js(__('¿Eliminar esta conversación?')))) return;
                try {
                    await fetch(`/rag/conversations/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    });
                    this.conversations = this.conversations.filter(c => c.id !== id);
                    if (this.conversationId === id) this.newConversation();
                } catch (e) {
                }
            },
            async send() {
                const question = this.question.trim();
                if (question === '' || this.loading) {
                    return;
                }

                this.error = null;
                this.messages.push({ role: 'user', text: question });
                this.question = '';
                this.loading = true;

                try {
                    const res = await fetch('{{ route('rag-query.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({
                            question,
                            project_id: this.projectId || null,
                            task_id: this.taskId || null,
                            conversation_id: this.conversationId,
                        }),
                    });

                    const data = await res.json();

                    if (! res.ok) {
                        this.messages.pop();
                        this.error = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Ocurrió un error al consultar el asistente.');
                    } else {
                        this.messages.push({ role: 'assistant', text: data.answer, sources: data.sources || [] });

                        if (! this.conversationId) {
                            this.conversationId = data.conversation_id;
                            this.conversations.unshift({
                                id: data.conversation_id,
                                title: data.conversation_title,
                                project_id: this.projectId || null,
                                task_id: this.taskId || null,
                            });
                        } else {
                            const existing = this.conversations.find(c => c.id === this.conversationId);
                            if (existing) {
                                this.conversations = [existing, ...this.conversations.filter(c => c.id !== this.conversationId)];
                            }
                        }
                    }
                } catch (e) {
                    this.messages.pop();
                    this.error = 'No se pudo contactar al asistente. Verifica tu conexión.';
                } finally {
                    this.loading = false;
                    this.$nextTick(() => this.$refs.messagesEnd?.scrollIntoView({ behavior: 'smooth' }));
                }
            },
         }"
    >
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

        <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
             class="fixed lg:static inset-y-0 left-0 z-40 w-72 lg:w-64 shrink-0 transition-transform duration-200 lg:transition-none">
            <x-ui.card padding="p-0" class="h-full flex flex-col">
                <div class="p-3 border-b border-gray-100">
                    <button @click="newConversation()"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Nueva conversación') }}
                    </button>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
                    <template x-if="conversations.length === 0">
                        <p class="text-xs text-gray-400 text-center py-8 px-3">{{ __('Todavía no tienes conversaciones guardadas.') }}</p>
                    </template>
                    <template x-for="conversation in conversations" :key="conversation.id">
                        <div :class="conversation.id === conversationId ? 'bg-emerald-50' : 'hover:bg-gray-50'"
                             class="group flex items-center gap-1 px-3 py-2.5 cursor-pointer transition"
                             @click="loadConversation(conversation.id)">
                            <span class="flex-1 text-sm truncate" :class="conversation.id === conversationId ? 'text-emerald-800 font-medium' : 'text-gray-700'" x-text="conversation.title"></span>
                            <button @click.stop="deleteConversation(conversation.id)" title="{{ __('Eliminar') }}"
                                    aria-label="{{ __('Eliminar conversación') }}"
                                    class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 p-2 -m-1 min-w-[44px] min-h-[44px] inline-flex items-center justify-center rounded text-gray-300 hover:text-red-600 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </x-ui.card>
        </div>

        <div class="flex-1 min-w-0 flex flex-col h-full min-h-0 space-y-4">
            <x-ui.card padding="p-4" class="shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" aria-label="{{ __('Abrir conversaciones') }}" class="lg:hidden p-2 -ml-2 min-w-[44px] min-h-[44px] inline-flex items-center justify-center text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Proyecto (opcional)') }}</label>
                            <select x-model="projectId" @change="taskId = ''"
                                    class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                                <option value="">{{ __('Todos los proyectos accesibles') }}</option>
                                <template x-for="project in projects" :key="project.id">
                                    <option :value="project.id" x-text="project.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Tarea (opcional)') }}</label>
                            <select x-model="taskId" :disabled="! projectId"
                                    class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="">{{ __('Ninguna tarea específica') }}</option>
                                <template x-for="task in tasksForSelectedProject" :key="task.id">
                                    <option :value="task.id" x-text="task.title"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-0" class="flex-1 min-h-0 flex flex-col">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-4" x-ref="messagesPane">
                    <template x-if="messages.length === 0">
                        <p class="text-sm text-gray-400 text-center py-16">
                            {{ __('Escribe una pregunta sobre un proyecto, tarea o documento para comenzar.') }}
                        </p>
                    </template>

                    <template x-for="(message, index) in messages" :key="index">
                        <div :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'"
                             class="animate-fade-in-up">
                            <div :class="message.role === 'user' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-800'"
                                 class="max-w-lg rounded-xl px-4 py-3 text-sm whitespace-pre-line break-words shadow-sm">
                                <p x-text="message.text"></p>
                                <template x-if="message.role === 'assistant' && message.sources && message.sources.length > 0">
                                    <div class="mt-2 pt-2 border-t border-gray-200 flex flex-wrap gap-1">
                                        <template x-for="(source, sIndex) in message.sources" :key="sIndex">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white text-gray-600 ring-1 ring-gray-300 transition-colors hover:bg-emerald-50 hover:text-emerald-700 hover:ring-emerald-200"
                                                  :title="'score: ' + source.score.toFixed(2)">
                                                [<span x-text="sIndex + 1"></span>] <span x-text="source.document_title"></span>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="loading">
                        <div class="flex justify-start animate-fade-in-up">
                            <div class="bg-gray-100 text-gray-500 rounded-xl px-4 py-3 text-sm flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-typing-bounce" style="animation-delay: 0ms"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-typing-bounce" style="animation-delay: 150ms"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-typing-bounce" style="animation-delay: 300ms"></span>
                            </div>
                        </div>
                    </template>

                    <div x-ref="messagesEnd"></div>
                </div>

                <div class="border-t border-gray-100 p-4 shrink-0">
                    <template x-if="error">
                        <p class="text-sm text-red-600 mb-2" x-text="error"></p>
                    </template>

                    <form @submit.prevent="send()" class="flex gap-3">
                        <textarea x-model="question"
                                  @keydown.enter.prevent="send()"
                                  rows="1"
                                  placeholder="{{ __('Escribe tu pregunta…') }}"
                                  class="flex-1 resize-none border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-lg shadow-sm text-sm"
                        ></textarea>
                        <x-ui.primary-button type="submit" x-bind:disabled="loading" class="disabled:opacity-50">
                            {{ __('Enviar') }}
                        </x-ui.primary-button>
                    </form>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
