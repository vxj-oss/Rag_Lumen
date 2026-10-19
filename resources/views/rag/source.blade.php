<x-app-layout>
    <x-slot name="header"><x-ui.page-header :title="$document->titulo" :subtitle="__('Texto extraído del documento para las consultas del asistente')" :eyebrow="__('Fuentes verificables')" /></x-slot>
    <div class="mx-auto max-w-4xl space-y-5 px-4 py-8 sm:px-6">
        <a href="{{ route('rag-query.index') }}" class="workspace-button">← {{ __('Volver al asistente') }}</a>
        <p class="text-sm text-slate-600">{{ $document->nombre_archivo }} · {{ __('Este contenido es una extracción del documento; consulta el original para comprobar su formato completo.') }}</p>
        @forelse ($chunks as $chunk)
            <x-ui.card><h2 class="mb-3 text-sm font-semibold text-emerald-800">{{ __('Fragmento') }} {{ $chunk->indice_fragmento + 1 }}</h2><p class="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-800">{{ $chunk->contenido }}</p></x-ui.card>
        @empty
            <div class="workspace-empty"><h2 class="workspace-section-title">{{ __('Todavía no hay texto disponible') }}</h2><p class="mt-2 text-sm text-slate-600">{{ __('El documento puede estar pendiente de procesamiento. Revisa su estado en Documentos.') }}</p><a href="{{ route('rag-documents.index') }}" class="workspace-button mt-4">{{ __('Ir a documentos') }}</a></div>
        @endforelse
        {{ $chunks->links() }}
    </div>
</x-app-layout>
