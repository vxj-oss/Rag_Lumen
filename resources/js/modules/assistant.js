export function registerAssistant(Alpine) {
    Alpine.data('assistantClient', (config) => ({
        open: !!config.embedded, embedded: !!config.embedded, loading: false, historyOpen: false,
        question: '', messages: [], sources: [], conversations: [], conversationId: null,
        projectId: config.projectId || '', taskId: config.taskId || '', projects: config.projects || [],
        error: '', recording: false, transcribing: false, speaking: false, voiceEnabled: false,
        mediaRecorder: null, mediaStream: null, audioChunks: [], recordingTimer: null,
        requestController: null, requestNumber: 0, destroyed: false, settingsOpen: false,
        recognition: null, dictationBase: '', finalTranscript: '', dictationHint: '', cancelAudio: false,
        voiceController: null, startingVoice: false,
        dockLeft: false,
        get storageKey() { return `assistant.${config.userId}.${config.projectId || 'all'}.${config.taskId || 'all'}`; },
        get availableTasks() { return this.projects.find(p => String(p.id) === String(this.projectId))?.tasks || []; },
        get status() { return this.recording ? 'Escuchando…' : this.transcribing ? 'Transcribiendo…' : this.loading ? 'Consultando…' : this.speaking ? 'Hablando…' : 'Disponible'; },
        async init() {
            try { this.voiceEnabled = localStorage.getItem(`assistant.voice.${config.userId}`) === '1'; } catch (_) {}
            try { this.dockLeft = localStorage.getItem(`assistant.dock.${config.userId}`) === 'left'; } catch (_) {}
            if (this.embedded) await this.initializeConversation();
        },
        async initializeConversation() {
            await this.loadHistory();
            const requested = this.embedded && new URLSearchParams(location.search).get('conversation');
            if (requested && !this.conversationId) { await this.loadConversation(requested); return; }
            try { const id = localStorage.getItem(this.storageKey); if (id && !this.conversationId) await this.loadConversation(id); } catch (_) {}
        },
        async toggle() {
            this.open = !this.open;
            if (this.open) { await this.initializeConversation(); this.$nextTick(() => this.$refs.question?.focus()); }
            else this.stopAll();
        },
        moveDock() { this.dockLeft = !this.dockLeft; try { localStorage.setItem(`assistant.dock.${config.userId}`, this.dockLeft ? 'left' : 'right'); } catch (_) {} },
        async loadHistory() {
            try {
                const response = await fetch(config.historyUrl, { headers: { Accept: 'application/json' } });
                if (response.ok) this.conversations = (await response.json()).conversations;
            } catch (_) { this.error = 'No se pudo cargar el historial. Puedes iniciar una conversación.'; }
        },
        startNew() {
            if (this.loading || this.recording || this.transcribing) return;
            this.stopSpeaking(); this.conversationId = null; this.messages = []; this.error = ''; this.question = ''; this.historyOpen = false;
            if (this.embedded) { const url = new URL(location.href); url.searchParams.delete('conversation'); history.replaceState(null, '', url); }
            try { localStorage.removeItem(this.storageKey); } catch (_) {}
        },
        changeContext() { this.taskId = ''; this.startNew(); },
        async loadConversation(id) {
            if (this.loading || this.recording || this.transcribing) return;
            this.error = '';
            const sequence = ++this.requestNumber;
            try {
                const response = await fetch(config.conversationUrl.replace('__ID__', id), { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('La conversación ya no está disponible o cambió tu acceso.');
                const data = await response.json();
                if (sequence !== this.requestNumber || this.destroyed) return;
                this.conversationId = data.conversation.id;
                this.projectId = data.conversation.project_id || ''; this.taskId = data.conversation.task_id || '';
                this.messages = data.messages; this.historyOpen = false;
                try { localStorage.setItem(this.storageKey, this.conversationId); } catch (_) {}
                this.scrollMessages();
            } catch (error) { this.error = error.message; }
        },
        async send() {
            const question = this.question.trim();
            if (!question || this.loading || this.recording || this.transcribing) return;
            this.stopSpeaking(); this.error = ''; this.loading = true; this.question = '';
            this.messages.push({ role: 'user', text: question }); this.scrollMessages();
            this.requestController = new AbortController();
            const timeout = setTimeout(() => this.requestController?.abort(), 80000);
            try {
                const response = await fetch(config.sendUrl, { method: 'POST', headers: this.headers(), signal: this.requestController.signal,
                    body: JSON.stringify({ question, project_id: this.projectId || null, task_id: this.taskId || null, conversation_id: this.conversationId }) });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'No se pudo consultar. Revisa tu conexión o vuelve a ingresar.');
                if (this.destroyed) return;
                this.messages.push({ role: 'assistant', text: data.answer, sources: data.sources || [], action: data.action });
                this.conversationId = data.conversation_id;
                try { localStorage.setItem(this.storageKey, data.conversation_id); } catch (_) {}
                await this.loadHistory();
                if (this.voiceEnabled) this.speak(data.answer);
            } catch (error) {
                this.messages.pop(); this.question = question;
                this.error = error.name === 'AbortError' ? 'La consulta tardó demasiado. Tu pregunta se conserva para reintentar.' : error.message;
            } finally { clearTimeout(timeout); this.loading = false; this.requestController = null; this.scrollMessages(); }
        },
        headers(json = true) {
            const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content };
            if (json) headers['Content-Type'] = 'application/json';
            return headers;
        },
        draftProject(action) { return action.projects?.find(p => String(p.id) === String(action.draft.project_id)); },
        async createProject(message) {
            const action = message.action;
            if (!action || action.submitting || action.created) return;
            action.submitting = true; action.error = '';
            const d = action.draft;
            try {
                const url = new URL(action.url, location.origin);
                if (url.origin !== location.origin) throw new Error('Dirección de creación inválida.');
                const response = await fetch(url, { method: 'POST', headers: this.headers(), body: JSON.stringify({ codigo: d.code, nombre: d.name, descripcion: d.description, tipo: d.type, cliente_id: d.client_id || null,
                    empleado_responsable_id: d.responsible_id || null, empleado_gerente_id: d.manager_id || null, area_ids: d.area_ids, fecha_inicio: d.start, fecha_fin_estimada: d.end, estado: 'planning', prioridad: d.priority, presupuesto: d.budget || null }) });
                const data = await response.json();
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'No se pudo crear el proyecto.');
                const destination = new URL(data.project.url, location.origin);
                if (destination.origin !== location.origin) throw new Error('Respuesta inválida.');
                action.created = true; action.createdUrl = destination.href;
                message.text = 'Proyecto creado: ' + data.project.title + '. Estado inicial: planificación.';
            } catch (error) { action.error = error.message; }
            finally { action.submitting = false; this.scrollMessages(); }
        },
        async createActivity(message) {
            const action = message.action;
            if (!action || action.submitting || action.created) return;
            const p = this.draftProject(action), d = action.draft;
            if (!p?.state_id) { action.error = 'Selecciona un proyecto con un estado pendiente configurado.'; return; }
            if (!d.title.trim() || !d.assignee_id || !d.tasks.length || d.tasks.some(t => !t.title.trim())) { action.error = 'Completa el título, responsable y al menos una tarea con título.'; return; }
            action.submitting = true; action.error = '';
            try {
                const url = new URL(action.url, location.origin);
                if (url.origin !== location.origin) throw new Error('Dirección de creación inválida.');
                const response = await fetch(url, { method: 'POST', headers: this.headers(), body: JSON.stringify({
                    proyecto_id: d.project_id, asignado_a: d.assignee_id, titulo: d.title, descripcion: d.description,
                    prioridad: d.priority, estado_id: p.state_id, fecha_inicio: d.start || null, fecha_vencimiento: d.due || null,
                    tareas: d.tasks.map(t => ({ titulo: t.title, descripcion: t.description || '' })),
                }) });
                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'No se pudo guardar.');
                }
                const saved = await response.json();
                if (!saved.task?.id || !saved.task?.url) throw new Error('No se recibió la confirmación de creación. Revisa Actividades antes de reintentar.');
                const destination = new URL(saved.task.url, location.origin);
                if (destination.origin !== location.origin || !/\/tasks\/\d+$/.test(destination.pathname)) throw new Error('No se pudo confirmar la creación. Revisa tu sesión y la sección Actividades antes de reintentar.');
                action.created = true; action.createdUrl = destination.href;
                message.text = 'Actividad creada correctamente: ' + d.title + '. Incluye ' + d.tasks.length + ' tarea(s).';
            } catch (error) { action.error = error.message; }
            finally { action.submitting = false; this.scrollMessages(); }
        },
        sourceUrl(source) {
            const value = source.url || (source.document_id ? config.sourceUrl.replace('__ID__', source.document_id) : '');
            try { const url = new URL(value, location.origin); return url.origin === location.origin && /^https?:$/.test(url.protocol) ? url.href : '#'; } catch (_) { return '#'; }
        },
        toggleVoice() {
            this.voiceEnabled = !this.voiceEnabled; if (!this.voiceEnabled) this.stopSpeaking();
            try { localStorage.setItem(`assistant.voice.${config.userId}`, this.voiceEnabled ? '1' : '0'); } catch (_) {}
        },
        speak(text) {
            if (!('speechSynthesis' in window)) { this.error = 'Este dispositivo no ofrece lectura por voz. La respuesta está disponible en texto.'; return; }
            this.stopSpeaking();
            const utterance = new SpeechSynthesisUtterance(text.replace(/[*#`]/g, '').slice(0, 900));
            utterance.lang = 'es-PE';
            const voices = speechSynthesis.getVoices().filter(v => v.lang.startsWith('es'));
            utterance.voice = voices.find(v => v.localService) || voices[0] || null;
            utterance.onstart = () => this.speaking = true;
            utterance.onend = utterance.onerror = () => this.speaking = false;
            speechSynthesis.speak(utterance);
        },
        stopSpeaking() { if ('speechSynthesis' in window) speechSynthesis.cancel(); this.speaking = false; },
        async startRecording() {
            if (this.loading || this.transcribing || this.recording || this.recognition || this.startingVoice) return;
            this.error = ''; this.dictationHint = ''; this.cancelAudio = false;
            const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (Recognition) { this.startLiveDictation(Recognition); return; }
            await this.startAudioRecording();
        },
        startLiveDictation(Recognition) {
            this.stopSpeaking();
            this.dictationBase = this.question.trim(); this.finalTranscript = '';
            const recognition = this.recognition = new Recognition();
            recognition.lang = 'es-PE'; recognition.continuous = true; recognition.interimResults = true;
            recognition.onstart = () => { this.recording = true; this.dictationHint = 'Habla: tus palabras aparecerán aquí.'; };
            recognition.onresult = event => {
                if (this.destroyed || this.recognition !== recognition) return;
                let interim = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const text = event.results[i][0].transcript;
                    if (event.results[i].isFinal) this.finalTranscript += text + ' ';
                    else interim += text;
                }
                this.question = [this.dictationBase, this.finalTranscript.trim(), interim.trim()].filter(Boolean).join(' ').slice(0, 2000);
            };
            recognition.onerror = event => {
                if (this.cancelAudio || this.destroyed) return;
                const blocked = ['not-allowed', 'service-not-allowed', 'audio-capture'].includes(event.error);
                this.error = blocked ? 'No se pudo acceder al micrófono. Revisa los permisos del navegador.' : event.error === 'no-speech' ? 'No detecté palabras. Acerca el micrófono y vuelve a intentar.' : 'El dictado en directo no está disponible. Prueba la grabación de voz en Opciones.';
            };
            recognition.onend = () => {
                if (this.recognition !== recognition) return;
                clearTimeout(this.recordingTimer); this.recognition = null; this.recording = false;
                this.dictationHint = this.question.trim() !== this.dictationBase ? 'Dictado listo. Revisa el texto y envíalo.' : '';
            };
            try { recognition.start(); this.recordingTimer = setTimeout(() => this.finishRecording(), 30000); }
            catch (_) { this.recognition = null; this.error = 'No se pudo iniciar el dictado. Prueba la grabación en Opciones.'; }
        },
        async startAudioRecording() {
            if (this.loading || this.transcribing || this.recording || this.recognition || this.startingVoice) return;
            this.error = ''; this.cancelAudio = false; this.dictationHint = '';
            if (!config.voiceAvailable) { this.error = 'Falta configurar la clave de Groq para transcribir grabaciones. Puedes usar el dictado en un navegador compatible.'; return; }
            if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) { this.error = 'El micrófono requiere un navegador compatible y HTTPS (o localhost). Puedes escribir.'; return; }
            this.stopSpeaking();
            this.startingVoice = true;
            try {
                this.mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                if (this.cancelAudio || this.destroyed || (!this.open && !this.embedded)) { this.mediaStream.getTracks().forEach(t => t.stop()); return; }
                const mimeType = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'].find(t => MediaRecorder.isTypeSupported(t));
                this.mediaRecorder = new MediaRecorder(this.mediaStream, mimeType ? { mimeType } : {});
                this.audioChunks = [];
                this.mediaRecorder.ondataavailable = event => { if (event.data.size) this.audioChunks.push(event.data); };
                this.mediaRecorder.onstop = async () => {
                    clearTimeout(this.recordingTimer); this.recording = false;
                    this.mediaStream?.getTracks().forEach(t => t.stop()); this.mediaStream = null;
                    if (!this.cancelAudio && !this.destroyed && (this.open || this.embedded)) await this.transcribe(new Blob(this.audioChunks, { type: this.mediaRecorder.mimeType }));
                    this.audioChunks = [];
                };
                this.dictationBase = this.question.trim(); this.mediaRecorder.start(); this.recording = true;
                this.dictationHint = 'Grabando. Pulsa detener para convertir tu voz en texto.';
                this.recordingTimer = setTimeout(() => this.finishRecording(), 30000);
            } catch (_) { this.error = 'No se pudo activar el micrófono. Revisa el permiso del navegador o escribe tu consulta.'; this.mediaStream?.getTracks().forEach(t => t.stop()); }
            finally { this.startingVoice = false; }
        },
        finishRecording() { this.recognition?.stop(); if (this.mediaRecorder?.state === 'recording') this.mediaRecorder.stop(); },
        async transcribe(blob) {
            if (blob.size < 100 || blob.size > 5 * 1024 * 1024) { this.error = 'La grabación está vacía o supera 5 MB.'; return; }
            this.transcribing = true;
            const form = new FormData();
            this.voiceController = new AbortController();
            const timeout = setTimeout(() => this.voiceController?.abort(), 60000);
            const extension = blob.type.includes('mp4') ? 'm4a' : blob.type.includes('ogg') ? 'ogg' : 'webm';
            form.append('audio', blob, `consulta.${extension}`);
            try {
                const response = await fetch(config.transcribeUrl, { method: 'POST', headers: this.headers(false), body: form, signal: this.voiceController.signal });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'No se pudo transcribir.');
                if (!String(data.text || '').trim()) throw new Error('No detecté palabras. Vuelve a grabar cerca del micrófono.');
                if (!this.destroyed) { this.question = [this.dictationBase, data.text.trim()].filter(Boolean).join(' ').slice(0, 2000); this.dictationHint = 'Dictado listo. Revisa el texto y envíalo.'; this.$nextTick(() => this.$refs.question?.focus()); }
            } catch (error) { this.error = error.name === 'AbortError' ? 'La transcripción tardó demasiado. Intenta una grabación más corta.' : error.message; this.dictationHint = ''; }
            finally { clearTimeout(timeout); this.voiceController = null; this.transcribing = false; }
        },
        scrollMessages() { this.$nextTick(() => { const pane = this.$refs.messages; if (pane) pane.scrollTop = pane.scrollHeight; }); },
        stopAll() { this.cancelAudio = true; this.stopSpeaking(); this.recognition?.abort(); this.finishRecording(); this.dictationHint = ''; },
        destroy() { this.destroyed = true; this.requestController?.abort(); this.voiceController?.abort(); this.stopAll(); clearTimeout(this.recordingTimer); this.mediaStream?.getTracks().forEach(t => t.stop()); },
    }));
}
