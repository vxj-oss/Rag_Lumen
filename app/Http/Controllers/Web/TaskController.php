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
use App\Support\TaskProgressRollup;
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
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
            'priorities' => Priority::cases(),
            'filters' => $request->only(['search', 'project_id', 'assigned_to', 'area_id', 'priority', 'mine']),
        ]);
    }

    public function board(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->filteredTasks($request)
            ->with(['state', 'assignee.area', 'project', 'statusHistory.user', 'progressUpdates.user'])
            ->orderBy('fecha_vencimiento')
            ->get();

        if ($request->filled('project_id')) {
            // Columnas únicas por slug, prefiriendo el estado propio del proyecto:
            // sin esto aparecen dos columnas idénticas y el arrastre entre ellas no hace nada.
            $statuses = TaskState::where(function ($query) use ($request) {
                $query->whereNull('proyecto_id')->orWhere('proyecto_id', $request->integer('project_id'));
            })->where('activo', true)->orderBy('posicion')->get()
                ->groupBy('slug')
                ->map(fn ($group) => $group->firstWhere('proyecto_id', $request->integer('project_id')) ?? $group->first())
                ->values();
        } else {
            $statuses = TaskState::whereNull('proyecto_id')->where('activo', true)->orderBy('posicion')->get()
                ->keyBy('slug');

            $extraIds = $tasks->pluck('estado_id')->filter()->unique()
                ->diff(TaskState::whereNull('proyecto_id')->pluck('id'))
                ->values();

            foreach (TaskState::whereIn('id', $extraIds)->orderBy('posicion')->get() as $extra) {
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
                'name' => $state->nombre,
                'color' => $state->color,
                'is_blocking' => $state->es_bloqueante,
                'count' => $cards->where('status_slug', $state->slug)->count(),
            ])->values(),
            'cards' => $cards,
            'kpis' => [
                'open' => $tasks->filter(fn (Task $task) => $task->isActiveState())->count(),
                'overdue' => $tasks->filter(fn (Task $task) => $task->isOverdue())->count(),
                'completed_week' => $tasks->filter(fn (Task $task) => $task->state?->slug === 'completada'
                    && $task->completado_en !== null
                    && $task->completado_en->gte(now()->startOfWeek()))->count(),
            ],
        ]);
    }

    private function filteredTasks(Request $request): Builder
    {
        $visible = $this->visibleTasksQuery();

        return (clone $visible)
            ->whereNull('tarea_padre_id')
            ->when($request->filled('search'), fn ($query) => $query->where('titulo', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('project_id'), fn ($query) => $query->where('proyecto_id', $request->integer('project_id')))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('asignado_a', $request->integer('assigned_to')))
            ->when($request->filled('priority'), fn ($query) => $query->where('prioridad', $request->string('priority')))
            ->when($request->filled('area_id'), fn ($query) => $query->whereHas('assignee', fn ($q) => $q->where('area_id', $request->integer('area_id'))))
            ->when($request->boolean('mine'), function ($query) use ($request) {
                $query->where('asignado_a', $request->user()->employee?->id);
            });
    }

    private function cardData(Task $task, $user): array
    {
        $assignee = $task->assignee;
        $mover = $task->statusHistory->first()?->user ?? $task->progressUpdates->first()?->user;

        return [
            'id' => $task->id,
            'code' => $task->codigo ?? '#'.$task->id,
            'title' => $task->titulo,
            'status_id' => $task->estado_id,
            'status_slug' => $task->state?->slug,
            'project_code' => $task->project?->codigo,
            'project_name' => $task->project?->nombre ?? 'Proyecto archivado',
            'assignee_name' => $assignee?->fullName(),
            'assignee_initials' => $assignee
                ? mb_strtoupper(mb_substr($assignee->nombres, 0, 1).mb_substr($assignee->apellidos, 0, 1))
                : null,
            'area' => $assignee?->area?->nombre,
            'priority' => $task->prioridad->value,
            'priority_label' => $task->prioridad->label(),
            'priority_color' => $task->prioridad->color(),
            'progress' => $task->porcentaje_progreso,
            'estimated_hours' => $task->horas_estimadas,
            'actual_hours' => $task->horas_reales,
            'due' => $this->dueData($task),
            'overdue' => $task->isOverdue(),
            'blocking' => $task->isBlockingState(),
            'blocked_reason' => $task->motivo_bloqueo,
            'last_mover_name' => $mover?->name,
            'last_mover_initial' => $mover ? mb_strtoupper(mb_substr($mover->name, 0, 1)) : null,
            'can_update' => $user->can('update', $task),
            'can_delete' => $user->can('delete', $task),
        ];
    }

    private function dueData(Task $task): ?array
    {
        if ($task->fecha_vencimiento === null) {
            return null;
        }

        $today = now()->startOfDay();
        $due = $task->fecha_vencimiento->copy()->startOfDay();
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
            $label = $task->fecha_vencimiento->format('d/m/Y');
        }

        return ['date' => $task->fecha_vencimiento->toDateString(), 'label' => $label];
    }

    private function scopedProjects($user)
    {
        return ProjectScope::apply(Project::orderBy('nombre'), $user)->get(['id', 'codigo', 'nombre']);
    }

    private function filterEmployees($user)
    {
        if ($user->isAdmin() || $user->isManager()) {
            return Employee::orderBy('nombres')->get(['id', 'nombres', 'apellidos']);
        }

        $employeeId = $user->employee?->id;

        if ($employeeId === null) {
            return collect();
        }

        $projectIds = Project::where('empleado_responsable_id', $employeeId)->pluck('id');

        return Employee::where('id', $employeeId)
            ->orWhereHas('projects', fn ($query) => $query->whereIn('proyectos.id', $projectIds))
            ->orWhereIn('id', Task::whereIn('proyecto_id', $projectIds)->select('asignado_a'))
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellidos']);
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->visibleTasksQuery()->with(['project', 'assignee', 'state'])->orderBy('fecha_vencimiento')->get();

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Código', 'Título', 'Proyecto', 'Asignado a', 'Estado', 'Prioridad', 'Vence', 'Avance %', 'Horas estimadas', 'Horas reales']);

            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->codigo ?? '#'.$task->id,
                    $task->titulo,
                    $task->project?->nombre ?? '—',
                    $task->assignee?->fullName() ?? '—',
                    $task->state?->nombre ?? $task->estado->label(),
                    $task->prioridad->label(),
                    $task->fecha_vencimiento?->toDateString() ?? '—',
                    $task->porcentaje_progreso,
                    $task->horas_estimadas ?? '—',
                    $task->horas_reales ?? '—',
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
                            $query->where('empleado_responsable_id', $employeeId);
                        });
                        $hasCondition = true;
                    }

                    if ($employeeId !== null) {
                        $query->orWhere('asignado_a', $employeeId);
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

        $state = TaskState::findOrFail($data['estado_id']);

        $data['creado_por'] = Auth::id();
        $data['estado'] = TaskState::enumForSlug($state->slug)?->value ?? 'pending';
        $data['completado_en'] = $state->slug === 'completada' ? now() : null;
        $data['porcentaje_progreso'] = $state->slug === 'completada' ? 100 : 0;

        $task = Task::create($data);
        $task->dependencies()->attach($dependencyIds);

        TaskStatusHistory::create([
            'tarea_id' => $task->id,
            'estado_origen_id' => null,
            'estado_destino_id' => $state->id,
            'usuario_id' => Auth::id(),
            'comentario' => 'Tarea creada.',
        ]);

        ActivityLogger::record($task, 'created', "Creó la tarea \"{$task->titulo}\".");

        if ($task->tarea_padre_id !== null) {
            TaskProgressRollup::recalculateAncestors($task, Auth::user());

            return redirect()
                ->route('tasks.show', $task->tarea_padre_id)
                ->with('status', 'Subtarea creada correctamente.');
        }

        return redirect()
            ->route('tasks.index', ['project_id' => $task->proyecto_id])
            ->with('status', 'Tarea creada correctamente.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load([
            'project', 'assignee.area', 'state', 'dependencies.state', 'dependents.state',
            'progressUpdates.user', 'statusHistory.user', 'tareaPadre',
            'subtareas' => fn ($query) => $query->orderBy('titulo'),
            'subtareas.state', 'subtareas.assignee',
        ]);

        $availableForDependency = Task::where('proyecto_id', $task->proyecto_id)
            ->where('id', '!=', $task->id)
            ->whereNotIn('id', $task->dependencies->pluck('id'))
            ->orderBy('titulo')
            ->get();

        $projectStates = TaskState::where(function ($query) use ($task) {
            $query->whereNull('proyecto_id')->orWhere('proyecto_id', $task->proyecto_id);
        })->where('activo', true)->orderBy('posicion')->get();

        $availableAsParent = Task::where('proyecto_id', $task->proyecto_id)
            ->where('id', '!=', $task->id)
            ->whereNotIn('id', $task->subtareas->pluck('id'))
            ->orderBy('titulo')
            ->get()
            ->reject(fn (Task $candidate) => $candidate->esDescendienteDe($task));

        return view('tasks.html.show', [
            'task' => $task,
            'availableForDependency' => $availableForDependency,
            'projectStates' => $projectStates,
            'availableAsParent' => $availableAsParent,
        ] + $this->formOptions());
    }

    public function update(UpdateTaskRequest $request, Task $task, RecordTaskProgressAction $recordProgress): RedirectResponse
    {
        $data = $request->validated();

        $before = $task->getAttributes();
        $previousParentId = $task->tarea_padre_id;

        if (array_key_exists('estado_id', $data) && (int) $data['estado_id'] !== (int) $task->estado_id) {
            $state = TaskState::findOrFail($data['estado_id']);

            $task->estado_id = $state->id;

            if ($enum = TaskState::enumForSlug($state->slug)) {
                $task->estado = $enum;
            }

            if ($state->slug === 'completada') {
                $task->porcentaje_progreso = 100;
                $task->completado_en ??= now();
            } else {
                $task->completado_en = null;
            }

            unset($data['estado_id']);

            TaskStatusHistory::create([
                'tarea_id' => $task->id,
                'estado_origen_id' => $before['estado_id'] ?? null,
                'estado_destino_id' => $state->id,
                'usuario_id' => $request->user()->id,
            ]);
        }

        $task->fill($data);
        $task->save();

        ActivityLogger::recordUpdate($task, $before, "la tarea \"{$task->titulo}\"");

        if ($task->state?->slug === 'completada' && $task->porcentaje_progreso < 100) {
            $recordProgress->execute($task, 100, 'Marcada como completada.', $request->user());
        }

        if ($previousParentId !== $task->tarea_padre_id) {
            if ($previousParentId !== null) {
                $previousParent = Task::find($previousParentId);

                if ($previousParent !== null) {
                    TaskProgressRollup::recalculate($previousParent, $request->user());
                }
            }

            TaskProgressRollup::recalculateAncestors($task, $request->user());
        }

        return redirect()
            ->route($task->tarea_padre_id !== null ? 'tasks.show' : 'tasks.index', $task->tarea_padre_id ?? [])
            ->with('status', 'Tarea actualizada correctamente.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $task->loadMissing('project');

        $validated = $request->validate([
            'status_id' => [
                'nullable', 'integer',
                Rule::exists('estados_tarea', 'id')->where(function ($query) use ($task) {
                    $query->where('activo', true)
                        ->where(function ($query) use ($task) {
                            $query->whereNull('proyecto_id')->orWhere('proyecto_id', $task->proyecto_id);
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
                    $query->where('proyecto_id', $task->proyecto_id)->orWhereNull('proyecto_id');
                })
                ->where('activo', true)
                ->orderByRaw('proyecto_id IS NULL')
                ->first();

            abort_if($state === null, 422, 'Estado no válido para este proyecto.');
        }

        $this->authorize('transitionTo', [$task, $state]);

        if ($state->es_bloqueante && blank($validated['blocked_reason'] ?? null) && blank($task->motivo_bloqueo)) {
            return response()->json([
                'message' => 'Mover a un estado de bloqueo requiere indicar el motivo.',
                'errors' => ['blocked_reason' => ['El motivo de bloqueo es obligatorio.']],
            ], 422);
        }

        $before = $task->getAttributes();
        $fromStatusId = $task->estado_id;

        $task->estado_id = $state->id;

        if ($enum = TaskState::enumForSlug($state->slug)) {
            $task->estado = $enum;
        }

        if ($state->slug === 'completada') {
            $task->porcentaje_progreso = 100;
            $task->completado_en ??= now();
        } else {
            $task->completado_en = null;
        }

        if (filled($validated['blocked_reason'] ?? null)) {
            $task->motivo_bloqueo = $validated['blocked_reason'];
        }

        $task->save();

        TaskStatusHistory::create([
            'tarea_id' => $task->id,
            'estado_origen_id' => $fromStatusId,
            'estado_destino_id' => $state->id,
            'usuario_id' => $request->user()->id,
            'comentario' => $validated['comment'] ?? null,
        ]);

        ActivityLogger::recordUpdate($task, $before, "la tarea \"{$task->titulo}\"");

        TaskProgressRollup::recalculateAncestors($task, $request->user());

        return response()->json([
            'id' => $task->id,
            'status_id' => $state->id,
            'status_slug' => $state->slug,
            'status' => $task->estado->value,
            'status_label' => $state->nombre,
            'status_color' => $state->color,
            'is_blocking' => $state->es_bloqueante,
            'progress_percentage' => $task->porcentaje_progreso,
        ]);
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        if ($task->tieneSubtareas()) {
            return redirect()
                ->back()
                ->with('toast_error', 'No puedes eliminar una tarea con subtareas. Elimínalas o quítalas primero.');
        }

        $parentId = $task->tarea_padre_id;

        ActivityLogger::record($task, 'deleted', "Eliminó la tarea \"{$task->titulo}\".");

        $task->delete();

        if ($parentId !== null) {
            $parent = Task::find($parentId);

            if ($parent !== null) {
                TaskProgressRollup::recalculate($parent, Auth::user());
            }

            return redirect()
                ->route('tasks.show', $parentId)
                ->with('status', 'Subtarea eliminada correctamente.');
        }

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Tarea eliminada correctamente.');
    }

    private function formOptions(): array
    {
        $projects = ProjectScope::apply(Project::orderBy('nombre'), Auth::user())->get();

        return [
            'projects' => $projects,
            'employees' => Employee::orderBy('nombres')->get(),
            'priorities' => Priority::cases(),
        ];
    }
}
