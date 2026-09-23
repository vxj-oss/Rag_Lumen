<?php

namespace App\RAG\VectorStores;

use App\Models\RagChunk;

class MySqlJsonVectorStore implements VectorStoreInterface
{
    public function store(RagChunk $chunk, array $vector): void
    {
        $chunk->update(['referencia_vector' => $vector]);
    }

    public function search(array $queryVector, int $limit, array $filters = []): array
    {
        $scored = [];

        RagChunk::query()
            ->whereNotNull('referencia_vector')
            ->whereHas('document', function ($query) use ($filters) {
                if (array_key_exists('project_ids', $filters)) {
                    $query->where(function ($query) use ($filters) {
                        $query->whereNull('proyecto_id');

                        if ($filters['project_ids'] !== []) {
                            $query->orWhereIn('proyecto_id', $filters['project_ids']);
                        }
                    });
                }
            })
            ->select(['id', 'referencia_vector'])
            ->orderBy('id')
            ->chunk(200, function ($chunks) use ($queryVector, &$scored) {
                foreach ($chunks as $chunk) {
                    $vector = $chunk->referencia_vector;

                    if (! is_array($vector) || $vector === []) {
                        continue;
                    }

                    $scored[] = [
                        'chunk_id' => $chunk->id,
                        'score' => $this->cosineSimilarity($queryVector, $vector),
                    ];
                }
            });

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    public function delete(RagChunk $chunk): void
    {
        $chunk->update(['referencia_vector' => null]);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * $b[$i];
            $normA += $value ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
