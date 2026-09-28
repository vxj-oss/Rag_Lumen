<?php

namespace App\Providers;

use App\Policies\AlertPolicy;
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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TextChunkerInterface::class, FixedSizeTextChunker::class);

        $this->app->bind(EmbeddingServiceInterface::class, fn () => match (config('rag.embedding.provider')) {
            'cohere' => new CohereEmbeddingService,
            default => new OllamaEmbeddingService,
        });

        $this->app->bind(AnswerGeneratorInterface::class, fn () => match (config('rag.completion.provider')) {
            'groq' => new GroqAnswerGenerator,
            default => new OllamaAnswerGenerator,
        });

        $this->app->bind(VectorStoreInterface::class, MySqlJsonVectorStore::class);
        $this->app->bind(RetrieverInterface::class, SemanticRetriever::class);
        $this->app->bind(ContextBuilderInterface::class, PromptContextBuilder::class);
    }

    public function boot(): void
    {
        Gate::policy(DatabaseNotification::class, AlertPolicy::class);
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('rag-query', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('rag-upload', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
