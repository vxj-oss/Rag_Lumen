<?php

namespace App\RAG\Retrievers;

use App\Models\RagChunk;
use App\Models\User;

interface RetrieverInterface
{
    
    public function retrieve(string $query, User $user, array $filters = []): array;
}
