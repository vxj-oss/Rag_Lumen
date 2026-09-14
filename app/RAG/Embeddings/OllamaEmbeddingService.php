<?php

namespace App\RAG\Embeddings;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaEmbeddingService implements EmbeddingServiceInterface
{
    public function embed(string $text): array
    {
        return $this->embedMany([$text])[0];
    }

    public function embedMany(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = Http::baseUrl(config('rag.embedding.base_url'))
            ->timeout(60)
            ->post('/api/embed', [
                'model' => config('rag.embedding.model'),
                'input' => array_values($texts),
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'El servicio de embeddings de Ollama respondió con un error: '.$response->body()
            );
        }

        $embeddings = $response->json('embeddings');

        if (! is_array($embeddings) || count($embeddings) !== count($texts)) {
            throw new RuntimeException('El servicio de embeddings de Ollama devolvió una respuesta inesperada.');
        }

        return $embeddings;
    }
}
