<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RagQuery\StoreRagQueryRequest;
use App\Models\Project;
use App\Models\RagConversation;
use App\Models\RagDocument;
use App\Models\RagMessage;
use App\Models\Task;
use App\Models\User;
use App\RAG\ContextBuilders\ContextBuilderInterface;
use App\RAG\Generators\AnswerGeneratorInterface;
use App\RAG\Retrievers\RetrieverInterface;
use App\Support\RagAccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RagQueryController extends Controller
{
    public function __construct(
        private RetrieverInterface $retriever,
        private ContextBuilderInterface $contextBuilder,
        private AnswerGeneratorInterface $generator,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', RagDocument::class);

        $accessibleIds = RagAccessScope::accessibleProjectIds(auth()->user());

        $projects = Project::query()
            ->when($accessibleIds !== null, fn ($query) => $query->whereIn('id', $accessibleIds))
            ->orderBy('name')
            ->with(['tasks' => fn ($query) => $query->orderBy('title')])
            ->get();

        $conversations = RagConversation::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get(['id', 'title', 'project_id', 'task_id', 'updated_at']);

        return view('rag-chat.html.index', ['projects' => $projects, 'conversations' => $conversations]);
    }

    
    public function showConversation(RagConversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === auth()->id(), 403);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'project_id' => $conversation->project_id,
                'task_id' => $conversation->task_id,
            ],
            'messages' => $conversation->messages->map(fn (RagMessage $message) => [
                'role' => $message->role,
                'text' => $message->content,
                'sources' => $message->sources ?? [],
            ]),
        ]);
    }

    public function destroyConversation(RagConversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === auth()->id(), 403);

        $conversation->delete();

        return response()->json(['status' => 'ok']);
    }

    public function store(StoreRagQueryRequest $request): JsonResponse
    {
        $user = $request->user();
        $project = null;
        $task = null;

        if ($request->filled('task_id')) {
            $task = Task::with('project')->findOrFail($request->validated('task_id'));
            $project = $task->project;
        } elseif ($request->filled('project_id')) {
            $project = Project::findOrFail($request->validated('project_id'));
        }

        if ($project !== null) {
            $this->ensureProjectIsAccessible($user, $project);
        }

        $conversation = $this->resolveConversation($request, $user, $project, $task);

        $filters = $project !== null ? ['project_ids' => [$project->id]] : [];
        $question = $request->validated('question');

        $retrieved = $this->retriever->retrieve($question, $user, $filters);
        $prompt = $this->contextBuilder->build($question, $retrieved, $project, $task);
        $answer = $this->generator->generate($prompt);

        $sources = collect($retrieved)->map(fn (array $result) => [
            'document_id' => $result['chunk']->document_id,
            'document_title' => $result['chunk']->document?->title,
            'chunk_index' => $result['chunk']->chunk_index,
            'score' => $result['score'],
        ])->values();

        $conversation->messages()->create(['role' => 'user', 'content' => $question]);
        $conversation->messages()->create(['role' => 'assistant', 'content' => $answer, 'sources' => $sources]);
        $conversation->touch();

        return response()->json([
            'answer' => $answer,
            'sources' => $sources,
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->title,
        ]);
    }

    private function resolveConversation(StoreRagQueryRequest $request, User $user, ?Project $project, ?Task $task): RagConversation
    {
        if ($request->filled('conversation_id')) {
            $conversation = RagConversation::findOrFail($request->validated('conversation_id'));
            abort_unless($conversation->user_id === $user->id, 403);

            return $conversation;
        }

        $question = $request->validated('question');

        return RagConversation::create([
            'user_id' => $user->id,
            'project_id' => $project?->id,
            'task_id' => $task?->id,
            'title' => Str::limit($question, 60),
        ]);
    }

    private function ensureProjectIsAccessible(User $user, Project $project): void
    {
        $accessibleIds = RagAccessScope::accessibleProjectIds($user);

        if ($accessibleIds !== null && ! in_array($project->id, $accessibleIds, true)) {
            abort(403, 'No tienes acceso a este proyecto.');
        }
    }
}
