<?php

namespace App\RAG\Embeddings;

interface EmbeddingServiceInterface
{
    
    public function embed(string $text): array;

    
    public function embedMany(array $texts): array;
}
