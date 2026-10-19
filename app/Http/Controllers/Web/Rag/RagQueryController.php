<?php

namespace App\Http\Controllers\Web\Rag;

use App\Http\Controllers\Controller;
use App\Http\Requests\RagQuery\StoreRagQueryRequest;
use App\Models\Project;
use App\Models\RagConversation;
use App\Models\RagDocument;
use App\Models\RagMessage;
use App\Models\Task;
use App\Models\User;
use App\Services\Assistant\AssistantService;
use App\Support\RagAccessScope;
use App\Support\WorkspaceScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RagQueryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RagDocument::class);

        $accessibleIds = RagAccessScope::accessibleProjectIds(auth()->user());

        $projects = Project::query()
            ->when($accessibleIds !== null, fn ($query) => $query->whereIn('id', $accessibleIds))
            ->orderBy('nombre')
            ->with(['tasks' => fn ($query) => $query->whereNull('tarea_padre_id')->whereIn('id', WorkspaceScope::tasks(auth()->user())->select('id'))->orderByDesc('id')->limit(100)->select('id', 'proyecto_id', 'titulo', 'asignado_a')])
            ->get(['id', 'nombre']);

        return view('rag-chat.index', ['projects' => $projects]);
    }

    public function showConversation(RagConversation $conversation): JsonResponse
    {
        abort_unless($conversation->usuario_id === auth()->id(), 403);
        if ($conversation->proyecto_id) {
            $this->ensureProjectIsAccessible(auth()->user(), Project::findOrFail($conversation->proyecto_id));
        }
        if ($conversation->tarea_id) {
            $this->authorize('view', Task::findOrFail($conversation->tarea_id));
        }

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->titulo,
                'project_id' => $conversation->proyecto_id,
                'task_id' => $conversation->tarea_id,
            ],
            'messages' => $conversation->messages->map(fn (RagMessage $message) => [
                'role' => $message->rol,
                'text' => $message->contenido,
                'sources' => $message->fuentes ?? [],
            ]),
        ]);
    }

    public function destroyConversation(RagConversation $conversation): JsonResponse
    {
        abort_unless($conversation->usuario_id === auth()->id(), 403);

        $conversation->delete();

        return response()->json(['status' => 'ok']);
    }

    public function store(StoreRagQueryRequest $request, AssistantService $assistant): JsonResponse
    {
        if (! app()->runningInConsole()) set_time_limit(75);
        $user = $request->user();
        $this->authorize('viewAny', RagDocument::class);
        $conversation = $request->filled('conversation_id')
            ? RagConversation::findOrFail($request->validated('conversation_id')) : new RagConversation;
        if ($conversation->exists) {
            abort_unless($conversation->usuario_id === $user->id, 403);
        }
        $project = null;
        $task = null;
        $taskId = $request->filled('task_id') ? $request->integer('task_id') : $conversation->tarea_id;
        $projectId = $request->filled('project_id') ? $request->integer('project_id') : $conversation->proyecto_id;
        if ($taskId) {
            $task = Task::with('project')->findOrFail($taskId);
            $this->authorize('view', $task);
            if ($request->filled('project_id')) {
                abort_unless($task->proyecto_id === $projectId, 422, 'La actividad no pertenece al proyecto seleccionado.');
            }
            $project = $task->project;
        } elseif ($projectId) {
            $project = Project::findOrFail($projectId);
        }
        if ($project) {
            $this->ensureProjectIsAccessible($user, $project);
        }
        $result = $assistant->answer($request->validated('question'), $user, $conversation, $project, $task);
        DB::transaction(function () use ($conversation, $request, $user, $project, $task, $result) {
            if (! $conversation->exists) {
                $conversation->fill(['usuario_id' => $user->id, 'titulo' => Str::limit($request->validated('question'), 60)]);
            }
            $conversation->fill(['proyecto_id' => $result['project_id'] ?? $project?->id, 'tarea_id' => $task?->id, 'contexto' => $result['context'] ?? $conversation->contexto])->save();
            $conversation->messages()->create(['rol' => 'user', 'contenido' => $request->validated('question')]);
            $conversation->messages()->create(['rol' => 'assistant', 'contenido' => $result['answer'], 'fuentes' => $result['sources'] ?? []]);
            $conversation->touch();
        });

        return response()->json(['answer' => $result['answer'], 'sources' => $result['sources'] ?? [], 'action' => $result['action'] ?? null, 'conversation_id' => $conversation->id, 'conversation_title' => $conversation->titulo]);
    }

    public function conversations(): JsonResponse
    {
        $this->authorize('viewAny', RagDocument::class);

        return response()->json(['conversations' => RagConversation::where('usuario_id', auth()->id())->latest('updated_at')->limit(30)->get(['id', 'titulo', 'proyecto_id', 'tarea_id'])->map(fn ($c) => ['id' => $c->id, 'title' => $c->titulo, 'project_id' => $c->proyecto_id, 'task_id' => $c->tarea_id])]);
    }

    private function ensureProjectIsAccessible(User $user, Project $project): void
    {
        $accessibleIds = RagAccessScope::accessibleProjectIds($user);

        if ($accessibleIds !== null && ! in_array($project->id, $accessibleIds, true)) {
            abort(403, 'No tienes acceso a este proyecto.');
        }
    }
}
