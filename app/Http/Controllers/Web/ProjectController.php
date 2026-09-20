<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Area;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\TaskState;
use App\Services\ProjectDecisionService;
use App\Services\ProjectMetricsService;
use App\Services\ProjectRiskService;
use App\Support\ActivityLogger;
use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use App\Support\Enums\TaskStatus;
use App\Support\ProjectScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectRiskService $riskService): View
    {
        $this->authorize('viewAny', Project::class);

        $allProjects = ProjectScope::apply(Project::with(['tasks', 'members']), $request->user())->get();
        $risksById = $allProjects->mapWithKeys(fn (Project $project) => [
            $project->id => $riskService->calculate($project),
        ]);
        $highRiskCount = $risksById->filter(fn ($risk) => in_array($risk['level']->value, ['high', 'critical'], true))->count();

        $projects = ProjectScope::apply(Project::query(), $request->user())
            ->with(['responsibleEmployee', 'client'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->integer('client_id')))
            ->when($request->filled('area_id'), fn ($query) => $query->whereHas('areas', fn ($q) => $q->where('areas.id', $request->integer('area_id'))))
            ->orderByDesc('created_at')
            ->paginate(5)
            ->withQueryString();

        return view('projects.html.index', [
            'projects' => $projects,
            'risks' => $risksById,
            'totalCount' => $allProjects->count(),
            'inProgressCount' => $allProjects->where('status', ProjectStatus::InProgress)->count(),
            'blockedCount' => $allProjects->where('status', ProjectStatus::Blocked)->count(),
            'highRiskCount' => $highRiskCount,
            'filters' => $request->only(['search', 'status', 'priority', 'client_id', 'area_id']),
        ] + $this->formOptions());
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $areaIds = $data['area_ids'] ?? [];
        unset($data['area_ids']);

        $project = Project::create($data);
        $project->areas()->sync($areaIds);
        TaskState::seedDefaults($project->id);

        ActivityLogger::record($project, 'created', "Creó el proyecto \"{$project->name}\".");

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyecto creado correctamente.');
    }

    public function show(
        Project $project,
        ProjectMetricsService $metricsService,
        ProjectRiskService $riskService,
        ProjectDecisionService $decisionService
    ): View {
        $this->authorize('view', $project);

        $project->load([
            'client',
            'manager',
            'responsibleEmployee',
            'areas',
            'tasks.assignee.area',
            'members' => function ($query) {
                $query->orderByPivot('status')->orderBy('first_name');
            },
        ]);

        $availableEmployees = $this->availableEmployees($project);

        return view('projects.html.show', [
            'project' => $project,
            'availableEmployees' => $availableEmployees,
            'areaProgress' => $this->areaProgress($project),
            'metrics' => $metricsService->forProject($project),
            'risk' => $riskService->recalculate($project),
            'decision' => $decisionService->evaluate($project),
        ] + $this->formOptions());
    }

    public function export(
        Project $project,
        ProjectMetricsService $metricsService,
        ProjectRiskService $riskService,
        ProjectDecisionService $decisionService
    ): Response {
        $this->authorize('view', $project);

        $project->load(['responsibleEmployee', 'tasks.assignee', 'members']);

        return Pdf::loadView('reports.project', [
            'project' => $project,
            'metrics' => $metricsService->forProject($project),
            'risk' => $riskService->recalculate($project),
            'decision' => $decisionService->evaluate($project),
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download('reporte-'.$project->code.'-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::with('responsibleEmployee')->orderByDesc('created_at')->get();

        return response()->streamDownload(function () use ($projects) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Código', 'Nombre', 'Tipo', 'Estado', 'Prioridad', 'Responsable', 'Fecha inicio', 'Fecha fin estimada', 'Riesgo', 'Puntaje de riesgo']);

            foreach ($projects as $project) {
                fputcsv($handle, [
                    $project->code,
                    $project->name,
                    $project->type->label(),
                    $project->status->label(),
                    $project->priority->label(),
                    $project->responsibleEmployee?->fullName() ?? '—',
                    $project->start_date?->toDateString() ?? '—',
                    $project->estimated_end_date?->toDateString() ?? '—',
                    $project->risk_level,
                    $project->risk_score,
                ]);
            }

            fclose($handle);
        }, 'proyectos-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $before = $project->getAttributes();

        $data = $request->validated();
        $areaIds = $data['area_ids'] ?? null;
        unset($data['area_ids']);

        $project->update($data);

        if (is_array($areaIds)) {
            $project->areas()->sync($areaIds);
        }

        ActivityLogger::recordUpdate($project, $before, "el proyecto \"{$project->name}\"");

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        ActivityLogger::record($project, 'deleted', "Eliminó el proyecto \"{$project->name}\".");

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyecto eliminado correctamente.');
    }

    private function formOptions(): array
    {
        return [
            'types' => ProjectType::cases(),
            'statuses' => ProjectStatus::cases(),
            'priorities' => Priority::cases(),
            'employees' => Employee::orderBy('first_name')->get(),
            'managers' => Employee::whereHas('user.roles', fn ($q) => $q->whereIn('name', ['administrator', 'manager']))
                ->orderBy('first_name')->get(),
            'leaders' => Employee::whereHas('user.roles', fn ($q) => $q->whereIn('name', ['administrator', 'project_lead']))
                ->orderBy('first_name')->get(),
            'clients' => Client::where('status', 'active')->orderBy('name')->get(),
            'areas' => Area::where('active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * Empleados disponibles para agregar al proyecto: si el proyecto tiene
     * áreas, solo empleados de esas áreas; si no, todos los no miembros.
     */
    private function availableEmployees(Project $project)
    {
        return Employee::whereNotIn('id', $project->members->pluck('id'))
            ->when(
                $project->areas->isNotEmpty(),
                fn ($query) => $query->whereIn('area_id', $project->areas->pluck('id'))
            )
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Avance por área participante: tareas cuyo asignado pertenece al área.
     */
    private function areaProgress(Project $project): array
    {
        return $project->areas->map(function ($area) use ($project) {
            $tasks = $project->tasks->filter(fn ($task) => $task->assignee?->area_id === $area->id);
            $total = $tasks->count();
            $completed = $tasks->filter(fn ($task) => $task->status === TaskStatus::Completed)->count();

            $weight = $tasks->sum(fn ($task) => (float) ($task->estimated_hours ?? 1));

            $weighted = $weight > 0
                ? round($tasks->sum(fn ($task) => $task->progress_percentage * (float) ($task->estimated_hours ?? 1)) / $weight, 1)
                : 0.0;

            return [
                'area' => $area,
                'total' => $total,
                'completed' => $completed,
                'percent' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
                'weighted' => $weighted,
            ];
        })->all();
    }

    /**
     * Datos para filtros dinámicos: áreas del proyecto y empleados de esas áreas.
     */
    public function scopeData(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load('areas');

        $areaIds = $project->areas->pluck('id');

        $employees = Employee::when($areaIds->isNotEmpty(), fn ($query) => $query->whereIn('area_id', $areaIds))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'area_id']);

        return response()->json([
            'areas' => $project->areas->map(fn ($area) => ['id' => $area->id, 'name' => $area->name])->values(),
            'employees' => $employees->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->fullName(),
                'area_id' => $e->area_id,
            ])->values(),
            'states' => TaskState::where(function ($query) use ($project) {
                $query->whereNull('project_id')->orWhere('project_id', $project->id);
            })->where('active', true)->orderBy('position')
                ->get(['id', 'name', 'slug', 'color', 'is_initial', 'is_final', 'is_blocking'])
                ->values(),
            'tasks' => $project->tasks()->orderBy('title')->get(['id', 'code', 'title'])
                ->map(fn ($t) => ['id' => $t->id, 'code' => $t->code ?? '#'.$t->id, 'title' => $t->title])
                ->values(),
        ]);
    }
}
