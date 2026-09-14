<?php

namespace App\Jobs;

use App\Models\RagChunk;
use App\Models\RagDocument;
use App\RAG\Chunkers\TextChunkerInterface;
use App\RAG\Embeddings\EmbeddingServiceInterface;
use App\RAG\Loaders\DocxDocumentLoader;
use App\RAG\Loaders\PdfDocumentLoader;
use App\RAG\Loaders\TxtDocumentLoader;
use App\RAG\Services\TextNormalizer;
use App\RAG\VectorStores\VectorStoreInterface;
use App\Support\Enums\RagDocumentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessRagDocument implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;

    public function __construct(private int $ragDocumentId)
    {
    }

    
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(
        TextNormalizer $normalizer,
        TextChunkerInterface $chunker,
        EmbeddingServiceInterface $embeddingService,
        VectorStoreInterface $vectorStore,
    ): void
    {
        $document = RagDocument::find($this->ragDocumentId);

        if ($document === null) {
            return;
        }

        $document->update(['status' => RagDocumentStatus::Processing->value]);

        $loader = $this->resolveLoader($document->mime_type);

        if ($loader === null) {
            $this->markFailed($document, "No hay un extractor disponible para el tipo {$document->mime_type}.");

            return;
        }

        $filePath = Storage::disk(config('rag.storage_disk'))->path($document->file_path);
        $rawText = $loader->extractText($filePath);
        $normalizedText = $normalizer->normalize($rawText);

        if ($normalizedText === '') {
            $this->markFailed($document, 'No se pudo extraer texto del documento (puede estar vacío o ser una imagen escaneada).');

            return;
        }

        $textChunks = $chunker->chunk($normalizedText);

        if ($textChunks === []) {
            $this->markFailed($document, 'No se pudo generar ningún fragmento a partir del texto extraído.');

            return;
        }

        $vectors = $embeddingService->embedMany($textChunks);

        $document->chunks()->delete();

        foreach ($textChunks as $index => $content) {
            $chunk = RagChunk::create([
                'document_id' => $document->id,
                'content' => $content,
                'chunk_index' => $index,
                'metadata' => null,
                'vector_reference' => null,
            ]);

            $vectorStore->store($chunk, $vectors[$index]);
        }

        $document->update([
            'status' => RagDocumentStatus::Processed->value,
            'failure_reason' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $document = RagDocument::find($this->ragDocumentId);

        if ($document !== null) {
            $this->markFailed($document, $exception?->getMessage() ?? 'Error desconocido al procesar el documento.');
        }
    }

    private function resolveLoader(string $mimeType)
    {
        foreach ([new PdfDocumentLoader(), new DocxDocumentLoader(), new TxtDocumentLoader()] as $loader) {
            if ($loader->supports($mimeType)) {
                return $loader;
            }
        }

        return null;
    }

    private function markFailed(RagDocument $document, string $reason): void
    {
        $document->update([
            'status' => RagDocumentStatus::Failed->value,
            'failure_reason' => $reason,
        ]);
    }
}
