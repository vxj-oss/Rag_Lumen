<?php

namespace App\RAG\Retrievers;

use App\Models\RagChunk;
use App\Models\User;
use App\RAG\Embeddings\EmbeddingServiceInterface;
use App\RAG\VectorStores\VectorStoreInterface;
use App\Support\RagAccessScope;

class SemanticRetriever implements RetrieverInterface
{
    public function __construct(
        private EmbeddingServiceInterface $embeddingService,
        private VectorStoreInterface $vectorStore,
    ) {
    }

    public function retrieve(string $query, User $user, array $filters = []): array
    {
        $accessibleProjectIds = RagAccessScope::accessibleProjectIds($user);

        if ($accessibleProjectIds !== null) {
            $filters['project_ids'] = isset($filters['project_ids'])
                ? array_values(array_intersect($filters['project_ids'], $accessibleProjectIds))
                : $accessibleProjectIds;
        }

        $queryVector = $this->embeddingService->embed($query);
        $limit = (int) config('rag.retrieval_top_k');

        $results = $this->vectorStore->search($queryVector, $limit, $filters);

        if ($results === []) {
            return [];
        }

        $chunks = RagChunk::query()
            ->whereIn('id', array_column($results, 'chunk_id'))
            ->with('document')
            ->get()
            ->keyBy('id');

        $retrieved = [];

        foreach ($results as $result) {
            $chunk = $chunks->get($result['chunk_id']);

            if ($chunk !== null) {
                $retrieved[] = ['chunk' => $chunk, 'score' => $result['score']];
            }
        }

        return $retrieved;
    }
}
