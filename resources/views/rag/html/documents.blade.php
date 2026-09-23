<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Documentos')" :subtitle="__('Base de conocimiento para el agente inteligente (RAG)')">
            @can('create', \App\Models\RagDocument::class)
                <x-slot name="actions">
                    <x-ui.primary-button x-data @click="$dispatch('open-modal', 'upload-document')" class="min-h-[44px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        {{ __('Subir documento') }}
                    </x-ui.primary-button>
                </x-slot>
            @endcan
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-800">
                {{ __('Los documentos subidos quedan "Pendientes" hasta que el motor de procesamiento (próxima fase) extraiga su texto y genere los embeddings.') }}
            </div>

            <x-ui.card padding="p-0">
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Título') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Proyecto') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Tipo') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Subido por') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($documents as $document)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $document->titulo }}</div>
                                        <div class="text-xs text-gray-500">{{ $document->nombre_archivo }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $document->project?->nombre ?? __('General') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $document->tipo_origen->label() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-ui.badge :color="$document->estado->color()" :title="$document->motivo_fallo">{{ $document->estado->label() }}</x-ui.badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $document->uploader?->name ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($document->estado->value === 'failed')
                                                @can('update', $document)
                                                    <form action="{{ route('rag-documents.retry', $document) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-medium transition">{{ __('Reintentar') }}</button>
                                                    </form>
                                                @endcan
                                            @endif
                                            @can('delete', $document)
                                                <form action="{{ route('rag-documents.destroy', $document) }}" method="POST" @submit.prevent="if (confirm(@js(__('¿Eliminar este documento?')))) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium transition">{{ __('Eliminar') }}</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                        <p class="mt-2 text-sm text-gray-500">{{ __('Todavía no se han subido documentos.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden divide-y divide-gray-100">
                    @forelse ($documents as $document)
                        <div class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $document->titulo }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $document->nombre_archivo }}</p>
                                </div>
                                <x-ui.badge :color="$document->estado->color()" :title="$document->motivo_fallo">{{ $document->estado->label() }}</x-ui.badge>
                            </div>
                            <dl class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <dt class="font-medium text-gray-400">{{ __('Proyecto') }}</dt>
                                    <dd class="text-gray-700 truncate">{{ $document->project?->nombre ?? __('General') }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-400">{{ __('Tipo') }}</dt>
                                    <dd class="text-gray-700">{{ $document->tipo_origen->label() }}</dd>
                                </div>
                            </dl>
                            <div class="flex items-center gap-4 pt-1 text-sm border-t border-gray-100 mt-1 pt-3">
                                @if ($document->estado->value === 'failed')
                                    @can('update', $document)
                                        <form action="{{ route('rag-documents.retry', $document) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-medium min-h-[44px]">{{ __('Reintentar') }}</button>
                                        </form>
                                    @endcan
                                @endif
                                @can('delete', $document)
                                    <form action="{{ route('rag-documents.destroy', $document) }}" method="POST" @submit.prevent="if (confirm(@js(__('¿Eliminar este documento?')))) $el.submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium min-h-[44px]">{{ __('Eliminar') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <p class="text-4xl">📄</p>
                            <p class="mt-2 text-sm text-gray-500">{{ __('Todavía no se han subido documentos.') }}</p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            {{ $documents->links() }}
        </div>
    </div>

    @can('create', \App\Models\RagDocument::class)
        <x-ui.form-modal name="upload-document" :title="__('Subir documento')"
            :subtitle="__('PDF, Word, texto plano, Markdown o CSV — máx. 10 MB')" :action="route('rag-documents.store')"
            submit-label="{{ __('Subir') }}" multipart>
            <div>
                <x-forms.input-label for="title" :value="__('Título')" />
                <x-forms.text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
                <x-forms.input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-forms.input-label for="project_id" :value="__('Proyecto (opcional)')" />
                <x-forms.select id="project_id" name="project_id" class="mt-1 block w-full"
                    :options="['' => __('— Conocimiento general —')] + $projects->mapWithKeys(fn ($p) => [$p->id => $p->nombre])->toArray()"
                    :selected="old('project_id')" />
                <x-forms.input-error :messages="$errors->get('project_id')" class="mt-2" />
            </div>

            <div>
                <x-forms.input-label for="file" :value="__('Archivo')" />
                <input id="file" name="file" type="file" accept=".pdf,.docx,.txt,.md,.csv" required
                    class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" />
                <x-forms.input-error :messages="$errors->get('file')" class="mt-2" />
            </div>
        </x-ui.form-modal>

        @if ($errors->any())
            <div x-data x-init="$dispatch('open-modal', 'upload-document')"></div>
        @endif
    @endcan
</x-app-layout>
