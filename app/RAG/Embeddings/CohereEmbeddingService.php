<?php

namespace App\RAG\Embeddings;

use Illuminate\Support\Facades\Http;
use RuntimeException;


class CohereEmbeddingService implements EmbeddingServiceInterface
{
    public function embed(string $text): array
    {
        
        
        
        return $this->request([$text], 'search_query')[0];
    }

    public function embedMany(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        return $this->request(array_values($texts), 'search_document');
    }

    
    private function request(array $texts, string $inputType): array
    {
        $response = Http::withToken(config('rag.embedding.cohere.api_key'))
            ->timeout(60)
            ->post('https://api.cohere.com/v2/embed', [
                'model' => config('rag.embedding.cohere.model'),
                'texts' => $texts,
                'input_type' => $inputType,
                'embedding_types' => ['float'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'El servicio de embeddings de Cohere respondió con un error: '.$response->body()
            );
        }

        $embeddings = $response->json('embeddings.float');

        if (! is_array($embeddings) || count($embeddings) !== count($texts)) {
            throw new RuntimeException('El servicio de embeddings de Cohere devolvió una respuesta inesperada.');
        }

        return $embeddings;
    }
}
