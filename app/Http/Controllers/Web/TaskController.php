<?php

namespace App\Http\Controllers\Web;

use App\Actions\RecordTaskProgressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Enums\Priority;
use App\Support\Enums\RoleName;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        $visible = $this->visibleTasksQuery();

        $tasks = (clone $visible)
            ->with(['project', 'assignee'])
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->orderBy('due_date')
            ->paginate(5)
            ->withQueryString();

        $overdueCount = (clone $visible)
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        return view('tasks.html.index', [
            'tasks' => $tasks,
            'totalCount' => (clone $visible)->count(),
            'pendingCount' => (clone $visible)->where('status', TaskStatus::Pending->value)->count(),
            'blockedCount' => (clone $visible)->where('status', TaskStatus::Blocked->value)->count(),
            'overdueCount' => $overdueCount,
            'filters' => $request->only(['search', 'status', 'priority', 'project_id']),
        ] + $this->formOptions());
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->visibleTasksQuery()->with(['project', 'assignee'])->orderBy('due_date')->get();

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Título', 'Proyecto', 'Asignado a', 'Estado', 'Prioridad', 'Vence', 'Avance %', 'Horas estimadas', 'Horas reales']);

            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->title,
                    $task->project->name,
                    $task->assignee?->fullName() ?? '—',
                    $task->status->label(),
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

    private function visibleTasksQuery(): \Illuminate\Database\Eloquent\Builder
    {
        
        $user = Auth::user();

        return Task::query()->when(
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
        $data['created_by'] = Auth::id();
        $isCompleted = $data['status'] === TaskStatus::Completed->value;
        $data['completed_at'] = $isCompleted ? now() : null;
        $data['progress_percentage'] = $isCompleted ? 100 : 0;

        $task = Task::create($data);

        ActivityLogger::record($task, 'created', "Creó la tarea \"{$task->title}\".");

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Tarea creada correctamente.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load(['project', 'assignee', 'dependencies', 'dependents', 'progressUpdates.user']);

        $availableForDependency = Task::where('project_id', $task->project_id)
            ->where('id', '!=', $task->id)
            ->whereNotIn('id', $task->dependencies->pluck('id'))
            ->orderBy('title')
            ->get();

        return view('tasks.html.show', [
            'task' => $task,
            'availableForDependency' => $availableForDependency,
        ] + $this->formOptions());
    }

    public function update(UpdateTaskRequest $request, Task $task, RecordTaskProgressAction $recordProgress): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('status', $data)) {
            $isCompleted = $data['status'] === TaskStatus::Completed->value;
            $data['completed_at'] = $isCompleted ? ($task->completed_at ?? now()) : null;
        }

        $before = $task->getAttributes();

        $task->update($data);

        ActivityLogger::recordUpdate($task, $before, "la tarea \"{$task->title}\"");

        if (($data['status'] ?? null) === TaskStatus::Completed->value && $task->progress_percentage < 100) {
            $recordProgress->execute($task, 100, 'Marcada como completada.', $request->user());
        }

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Tarea actualizada correctamente.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,review,blocked,completed,cancelled'],
        ]);

        $status = TaskStatus::from($validated['status']);
        $before = $task->getAttributes();

        $task->status = $status;
        $task->completed_at = $status === TaskStatus::Completed ? ($task->completed_at ?? now()) : null;
        if ($status === TaskStatus::Completed) {
            $task->progress_percentage = 100;
        }
        $task->save();

        ActivityLogger::recordUpdate($task, $before, "la tarea \"{$task->title}\"");

        return response()->json([
            'id' => $task->id,
            'status' => $task->status->value,
            'status_label' => $task->status->label(),
            'status_color' => $task->status->color(),
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
        
        $user = Auth::user();

        $projects = $user->hasRole(RoleName::Administrator->value)
            ? Project::orderBy('name')->get()
            : Project::where('responsible_employee_id', $user->employee?->id)->orderBy('name')->get();

        return [
            'projects' => $projects,
            'employees' => Employee::orderBy('first_name')->get(),
            'statuses' => TaskStatus::cases(),
            'priorities' => Priority::cases(),
        ];
    }
}
