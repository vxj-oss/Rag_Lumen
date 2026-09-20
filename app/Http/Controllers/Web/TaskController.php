<?php

namespace App\Http\Controllers\Web;

use App\Actions\RecordTaskProgressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Area;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\TaskStatusHistory;
use App\Support\ActivityLogger;
use App\Support\Enums\Priority;
use App\Support\Enums\RoleName;
use App\Support\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        return view('tasks.html.index', [
            'projects' => $this->scopedProjects($request->user()),
            'filterEmployees' => $this->filterEmployees($request->user()),
            'areas' => Area::where('active', true)->orderBy('name')->get(),
            'priorities' => Priority::cases(),
            'filters' => $request->only(['search', 'project_id', 'assigned_to', 'area_id', 'priority', 'mine']),
        ]);
    }

    public function board(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->filteredTasks($request)
            ->with(['state', 'assignee.area', 'project', 'statusHistory.user', 'progressUpdates.user'])
            ->orderBy('due_date')
            ->get();

        if ($request->filled('project_id')) {
            // Columnas únicas por slug, prefiriendo el estado propio del proyecto:
            // sin esto aparecen dos columnas idénticas y el arrastre entre ellas no hace nada.
            $statuses = TaskState::where(function ($query) use ($request) {
                $query->whereNull('project_id')->orWhere('project_id', $request->integer('project_id'));
            })->where('active', true)->orderBy('position')->get()
                ->groupBy('slug')
                ->map(fn ($group) => $group->firstWhere('project_id', $request->integer('project_id')) ?? $group->first())
                ->values();
        } else {
            $statuses = TaskState::whereNull('project_id')->where('active', true)->orderBy('position')->get()
                ->keyBy('slug');

            $extraIds = $tasks->pluck('status_id')->filter()->unique()
                ->diff(TaskState::whereNull('project_id')->pluck('id'))
                ->values();

            foreach (TaskState::whereIn('id', $extraIds)->orderBy('position')->get() as $extra) {
                if (! $statuses->has($extra->slug)) {
                    $statuses->put($extra->slug, $extra);
                }
            }

            $statuses = $statuses->values();
        }

        $user = $request->user();
        $cards = $tasks->map(fn (Task $task) => $this->cardData($task, $user))->values();

        return response()->json([
            'columns' => $statuses->map(fn (TaskState $state) => [
                'id' => $state->id,
                'slug' => $state->slug,
                'name' => $state->name,
                'color' => $state->color,
                'is_blocking' => $state->is_blocking,
                'count' => $cards->where('status_slug', $state->slug)->count(),
            ])->values(),
            'cards' => $cards,
            'kpis' => [
                'open' => $tasks->filter(fn (Task $task) => $task->isActiveState())->count(),
                'overdue' => $tasks->filter(fn (Task $task) => $task->isOverdue())->count(),
                'completed_week' => $tasks->filter(fn (Task $task) => $task->state?->slug === 'completada'
                    && $task->completed_at !== null
                    && $task->completed_at->gte(now()->startOfWeek()))->count(),
            ],
        ]);
    }

    private function filteredTasks(Request $request): Builder
    {
        $visible = $this->visibleTasksQuery();

        return (clone $visible)
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('area_id'), fn ($query) => $query->whereHas('assignee', fn ($q) => $q->where('area_id', $request->integer('area_id'))))
            ->when($request->boolean('mine'), function ($query) use ($request) {
                $query->where('assigned_to', $request->user()->employee?->id);
            });
    }

    private function cardData(Task $task, $user): array
    {
        $assignee = $task->assignee;
        $mover = $task->statusHistory->first()?->user ?? $task->progressUpdates->first()?->user;

        return [
            'id' => $task->id,
            'code' => $task->code ?? '#'.$task->id,
            'title' => $task->title,
            'status_id' => $task->status_id,
            'status_slug' => $task->state?->slug,
            'project_code' => $task->project?->code,
            'project_name' => $task->project?->name ?? 'Proyecto archivado',
            'assignee_name' => $assignee?->fullName(),
            'assignee_initials' => $assignee
                ? mb_strtoupper(mb_substr($assignee->first_name, 0, 1).mb_substr($assignee->last_name, 0, 1))
                : null,
            'area' => $assignee?->area?->name,
            'priority' => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'priority_color' => $task->priority->color(),
            'progress' => $task->progress_percentage,
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => $task->actual_hours,
            'due' => $this->dueData($task),
            'overdue' => $task->isOverdue(),
            'blocking' => $task->isBlockingState(),
            'blocked_reason' => $task->blocked_reason,
            'last_mover_name' => $mover?->name,
            'last_mover_initial' => $mover ? mb_strtoupper(mb_substr($mover->name, 0, 1)) : null,
            'can_update' => $user->can('update', $task),
            'can_delete' => $user->can('delete', $task),
        ];
    }

    private function dueData(Task $task): ?array
    {
        if ($task->due_date === null) {
            return null;
        }

        $today = now()->startOfDay();
        $due = $task->due_date->copy()->startOfDay();
        $diff = $today->diffInDays($due, false);

        if ($diff < 0) {
            $days = abs((int) $diff);
            $label = $days === 1 ? '1 día de atraso' : "{$days} días de atraso";
        } elseif ($diff === 0) {
            $label = 'Vence hoy';
        } elseif ($diff === 1) {
            $label = 'Vence mañana';
        } elseif ($diff <= 7) {
            $label = "Vence en {$diff} días";
        } else {
            $label = $task->due_date->format('d/m/Y');
        }

        return ['date' => $task->due_date->toDateString(), 'label' => $label];
    }

    private function scopedProjects($user)
    {
        return ProjectScope::apply(Project::orderBy('name'), $user)->get(['id', 'code', 'name']);
    }

    private function filterEmployees($user)
    {
        if ($user->isAdmin() || $user->isManager()) {
            return Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        }

        $employeeId = $user->employee?->id;

        if ($employeeId === null) {
            return collect();
        }

        $projectIds = Project::where('responsible_employee_id', $employeeId)->pluck('id');

        return Employee::where('id', $employeeId)
            ->orWhereHas('projects', fn ($query) => $query->whereIn('projects.id', $projectIds))
            ->orWhereIn('id', Task::whereIn('project_id', $projectIds)->select('assigned_to'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->visibleTasksQuery()->with(['project', 'assignee', 'state'])->orderBy('due_date')->get();

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Código', 'Título', 'Proyecto', 'Asignado a', 'Estado', 'Prioridad', 'Vence', 'Avance %', 'Horas estimadas', 'Horas reales']);

            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->code ?? '#'.$task->id,
                    $task->title,
                    $task->project?->name ?? '—',
                    $task->assignee?->fullName() ?? '—',
                    $task->state?->name ?? $task->status->label(),
                    $task->priority->label(),
                    $task->due_date?->toDateString() ?? '—',
                    $task->progress_percentage,
                    $task->estimated_hours ?? '—',
                    $task->actual_hours ?? '—',
                ]);
            }

            fclose($handle);
        }, 'tareas-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function liveStatus(): JsonResponse
    {
        $visible = $this->visibleTasksQuery();

        $count = (clone $visible)->count();
        $lastUpdate = (clone $visible)->max('updated_at');

        return response()->json([
            'signature' => $count.':'.$lastUpdate,
        ]);
    }

    public function liveStatusOne(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json([
            'signature' => $task->updated_at,
        ]);
    }

    private function visibleTasksQuery(): Builder
    {

        $user = Auth::user();

        return Task::query()->whereHas('project')->when(
            ! $user->hasRole([RoleName::Administrator->value, RoleName::Manager->value]),
            function ($query) use ($user) {
                $employeeId = $user->employee?->id;

                $query->where(function ($query) use ($user, $employeeId) {
                    $hasCondition = false;

                    if ($user->hasRole(RoleName::ProjectLead->value) && $employeeId !== null) {
                        $query->orWhereHas('project', function ($query) use ($employeeId) {
                            $query->where('responsible_employee_id', $employeeId);
                        });
                        $hasCondition = true;
                    }

                    if ($employeeId !== null) {
                        $query->orWhere('assigned_to', $employeeId);
                        $hasCondition = true;
                    }

                    if (! $hasCondition) {
                        $query->whereRaw('1 = 0');
                    }
                });
            }
        );
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $dependencyIds = $data['dependency_ids'] ?? [];
        unset($data['dependency_ids'], $data['area_id']);

        $state = TaskState::findOrFail($data['status_id']);

        $data['created_by'] = Auth::id();
        $data['status'] = TaskState::enumForSlug($state->slug)?->value ?? 'pending';
        $data['completed_at'] = $state->slug === 'completada' ? now() : null;
        $data['progress_percentage'] = $state->slug === 'completada' ? 100 : 0;

        $task = Task::create($data);
        $task->dependencies()->attach($dependencyIds);

        TaskStatusHistory::create([
            'task_id' => $task->id,
            'from_status_id' => null,
            'to_status_id' => $state->id,
            'user_id' => Auth::id(),
            'comment' => 'Tarea creada.',
        ]);

        ActivityLogger::record($task, 'created', "Creó la tarea \"{$task->title}\".");

        return redirect()
            ->route('tasks.index', ['project_id' => $task->project_id])
            ->with('status', 'Tarea creada correctamente.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load(['project', 'assignee.area', 'state', 'dependencies.state', 'dependents.state', 'progressUpdates.user', 'statusHistory.user']);

        $availableForDependency = Task::where('project_id', $task->project_id)
            ->where('id', '!=', $task->id)
            ->whereNotIn('id', $task->dependencies->pluck('id'))
            ->orderBy('title')
            ->get();

        $projectStates = TaskState::where(function ($query) use ($task) {
            $query->whereNull('project_id')->orWhere('project_id', $task->project_id);
        })->where('active', true)->orderBy('position')->get();

        return view('tasks.html.show', [
            'task' => $task,
            'availableForDependency' => $availableForDependency,
            'projectStates' => $projectStates,
        ] + $this->formOptions());
    }

    public function update(UpdateTaskRequest $request, Task $task, RecordTaskProgressAction $recordProgress): RedirectResponse
    {
        $data = $request->validated();

        $before = $task->getAttributes();

        if (array_key_exists('status_id', $data) && (int) $data['status_id'] !== (int) $task->status_id) {
            $state = TaskState::findOrFail($data['status_id']);

            $task->status_id = $state->id;

            if ($enum = TaskState::enumForSlug($state->slug)) {
                $task->status = $enum;
            }

            if ($state->slug === 'completada') {
                $task->progress_percentage = 100;
                $task->completed_at ??= now();
            } else {
                $task->completed_at = null;
            }

            unset($data['status_id']);

            TaskStatusHistory::create([
                'task_id' => $task->id,
                'from_status_id' => $before['status_id'] ?? null,
                'to_status_id' => $state->id,
                'user_id' => $request->user()->id,
            ]);
        }

        $task->fill($data);
        $task->save();

        ActivityLogger::recordUpdate($task, $before, "la tarea \"{$task->title}\"");

        if ($task->state?->slug === 'completada' && $task->progress_percentage < 100) {
            $recordProgress->execute($task, 100, 'Marcada como completada.', $request->user());
        }

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Tarea actualizada correctamente.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $task->loadMissing('project');

        $validated = $request->validate([
            'status_id' => [
                'nullable', 'integer',
                Rule::exists('task_statuses', 'id')->where(function ($query) use ($task) {
                    $query->where('active', true)
                        ->where(function ($query) use ($task) {
                            $query->whereNull('project_id')->orWhere('project_id', $task->project_id);
                        });
                }),
            ],
            'status_slug' => ['nullable', 'string', 'max:60'],
            'blocked_reason' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ]);

        if (filled($validated['status_id'] ?? null)) {
            $state = TaskState::findOrFail($validated['status_id']);
        } else {
            $state = TaskState::where('slug', $validated['status_slug'] ?? '')
                ->where(function ($query) use ($task) {
                    $query->where('project_id', $task->project_id)->orWhereNull('project_id');
                })
                ->where('active', true)
                ->orderByRaw('project_id IS NULL')
                ->first();

            abort_if($state === null, 422, 'Estado no válido para este proyecto.');
        }

        $this->authorize('transitionTo', [$task, $state]);

        if ($state->is_blocking && blank($validated['blocked_reason'] ?? null) && blank($task->blocked_reason)) {
            return response()->json([
                'message' => 'Mover a un estado de bloqueo requiere indicar el motivo.',
                'errors' => ['blocked_reason' => ['El motivo de bloqueo es obligatorio.']],
            ], 422);
        }

        $before = $task->getAttributes();
        $fromStatusId = $task->status_id;

        $task->status_id = $state->id;

        if ($enum = TaskState::enumForSlug($state->slug)) {
            $task->status = $enum;
        }

        if ($state->slug === 'completada') {
            $task->progress_percentage = 100;
            $task->completed_at ??= now();
        } else {
            $task->completed_at = null;
        }

        if (filled($validated['blocked_reason'] ?? null)) {
            $task->blocked_reason = $validated['blocked_reason'];
        }

        $task->save();

        TaskStatusHistory::create([
            'task_id' => $task->id,
            'from_status_id' => $fromStatusId,
            'to_status_id' => $state->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'] ?? null,
        ]);

        ActivityLogger::recordUpdate($task, $before, "la tarea \"{$task->title}\"");

        return response()->json([
            'id' => $task->id,
            'status_id' => $state->id,
            'status_slug' => $state->slug,
            'status' => $task->status->value,
            'status_label' => $state->name,
            'status_color' => $state->color,
            'is_blocking' => $state->is_blocking,
            'progress_percentage' => $task->progress_percentage,
        ]);
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        ActivityLogger::record($task, 'deleted', "Eliminó la tarea \"{$task->title}\".");

        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Tarea eliminada correctamente.');
    }

    private function formOptions(): array
    {
        $projects = ProjectScope::apply(Project::orderBy('name'), Auth::user())->get();

        return [
            'projects' => $projects,
            'employees' => Employee::orderBy('first_name')->get(),
            'priorities' => Priority::cases(),
        ];
    }
}
