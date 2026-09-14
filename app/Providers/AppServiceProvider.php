<?php

namespace App\Providers;

use App\RAG\Chunkers\FixedSizeTextChunker;
use App\RAG\Chunkers\TextChunkerInterface;
use App\RAG\ContextBuilders\ContextBuilderInterface;
use App\RAG\ContextBuilders\PromptContextBuilder;
use App\RAG\Embeddings\CohereEmbeddingService;
use App\RAG\Embeddings\EmbeddingServiceInterface;
use App\RAG\Embeddings\OllamaEmbeddingService;
use App\RAG\Generators\AnswerGeneratorInterface;
use App\RAG\Generators\GroqAnswerGenerator;
use App\RAG\Generators\OllamaAnswerGenerator;
use App\RAG\Retrievers\RetrieverInterface;
use App\RAG\Retrievers\SemanticRetriever;
use App\RAG\VectorStores\MySqlJsonVectorStore;
use App\RAG\VectorStores\VectorStoreInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->bind(TextChunkerInterface::class, FixedSizeTextChunker::class);

        $this->app->bind(EmbeddingServiceInterface::class, fn () => match (config('rag.embedding.provider')) {
            'cohere' => new CohereEmbeddingService(),
            default => new OllamaEmbeddingService(),
        });

        $this->app->bind(AnswerGeneratorInterface::class, fn () => match (config('rag.completion.provider')) {
            'groq' => new GroqAnswerGenerator(),
            default => new OllamaAnswerGenerator(),
        });

        $this->app->bind(VectorStoreInterface::class, MySqlJsonVectorStore::class);
        $this->app->bind(RetrieverInterface::class, SemanticRetriever::class);
        $this->app->bind(ContextBuilderInterface::class, PromptContextBuilder::class);
    }

    public function boot(): void
    {
        
    }
}
