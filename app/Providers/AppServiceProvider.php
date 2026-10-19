<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
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
use App\Services\ProjectMetricsService;
use App\Services\TaskWorkflowNotifier;
use App\Support\Enums\TaskStatus;
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
        $this->app->scoped(ProjectMetricsService::class);
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
        \Illuminate\Pagination\Paginator::defaultView('components.ui.pagination');
        \Illuminate\Validation\Rules\Password::defaults(fn () => \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers());
        Task::saved(fn ($task) => app(ProjectMetricsService::class)->forget($task->proyecto_id));
        Task::deleted(fn ($task) => app(ProjectMetricsService::class)->forget($task->proyecto_id));
        Project::saved(fn ($project) => app(ProjectMetricsService::class)->forget($project->id));
        Task::created(fn ($task) => app(TaskWorkflowNotifier::class)->schedule($task, 'assigned'));
        Task::updated(function ($task) {
            $notifier = app(TaskWorkflowNotifier::class);
            if ($task->wasChanged('asignado_a')) {
                $notifier->schedule($task, 'assigned');
            }
            if ($task->wasChanged('estado')) {
                if ($task->estado === TaskStatus::Review) {
                    $notifier->schedule($task, 'review');
                } elseif ($task->estado === TaskStatus::Completed) {
                    $notifier->schedule($task, 'approved');
                } elseif ($task->motivo_rechazo && $task->wasChanged('motivo_rechazo')) {
                    $notifier->schedule($task, 'returned');
                }
            }
        });
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
