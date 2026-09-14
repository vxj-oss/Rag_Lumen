<?php

namespace App\RAG\VectorStores;

use App\Models\RagChunk;

interface VectorStoreInterface
{
    
    public function store(RagChunk $chunk, array $vector): void;

    
    public function search(array $queryVector, int $limit, array $filters = []): array;

    public function delete(RagChunk $chunk): void;
}
