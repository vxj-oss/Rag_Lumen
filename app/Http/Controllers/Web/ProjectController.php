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
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('estado', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('prioridad', $request->string('priority')))
            ->when($request->filled('client_id'), fn ($query) => $query->where('cliente_id', $request->integer('client_id')))
            ->when($request->filled('area_id'), fn ($query) => $query->whereHas('areas', fn ($q) => $q->where('areas.id', $request->integer('area_id'))))
            ->orderByDesc('created_at')
            ->paginate(5)
            ->withQueryString();

        return view('projects.html.index', [
            'projects' => $projects,
            'risks' => $risksById,
            'totalCount' => $allProjects->count(),
            'inProgressCount' => $allProjects->where('estado', ProjectStatus::InProgress)->count(),
            'blockedCount' => $allProjects->where('estado', ProjectStatus::Blocked)->count(),
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

        ActivityLogger::record($project, 'created', "Creó el proyecto \"{$project->nombre}\".");

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
                $query->orderByPivot('estado')->orderBy('nombres');
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
            ->download('reporte-'.$project->codigo.'-'.now()->format('Y-m-d').'.pdf');
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
                    $project->codigo,
                    $project->nombre,
                    $project->tipo->label(),
                    $project->estado->label(),
                    $project->prioridad->label(),
                    $project->responsibleEmployee?->fullName() ?? '—',
                    $project->fecha_inicio?->toDateString() ?? '—',
                    $project->fecha_fin_estimada?->toDateString() ?? '—',
                    $project->nivel_riesgo,
                    $project->puntuacion_riesgo,
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

        ActivityLogger::recordUpdate($project, $before, "el proyecto \"{$project->nombre}\"");

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        ActivityLogger::record($project, 'deleted', "Eliminó el proyecto \"{$project->nombre}\".");

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
            'employees' => Employee::orderBy('nombres')->get(),
            'managers' => Employee::whereHas('user.roles', fn ($q) => $q->whereIn('name', ['administrator', 'manager']))
                ->orderBy('nombres')->get(),
            'leaders' => Employee::whereHas('user.roles', fn ($q) => $q->whereIn('name', ['administrator', 'project_lead']))
                ->orderBy('nombres')->get(),
            'clients' => Client::where('estado', 'active')->orderBy('nombre')->get(),
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
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
            ->orderBy('nombres')
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
            $completed = $tasks->filter(fn ($task) => $task->estado === TaskStatus::Completed)->count();

            $weight = $tasks->sum(fn ($task) => (float) ($task->horas_estimadas ?? 1));

            $weighted = $weight > 0
                ? round($tasks->sum(fn ($task) => $task->porcentaje_progreso * (float) ($task->horas_estimadas ?? 1)) / $weight, 1)
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
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellidos', 'area_id']);

        return response()->json([
            'areas' => $project->areas->map(fn ($area) => ['id' => $area->id, 'name' => $area->nombre])->values(),
            'employees' => $employees->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->fullName(),
                'area_id' => $e->area_id,
            ])->values(),
            'states' => TaskState::where(function ($query) use ($project) {
                $query->whereNull('proyecto_id')->orWhere('proyecto_id', $project->id);
            })->where('activo', true)->orderBy('posicion')
                ->get(['id', 'nombre', 'slug', 'color', 'es_inicial', 'es_final', 'es_bloqueante'])
                ->map(fn ($s) => [
                    'id' => $s->id, 'name' => $s->nombre, 'slug' => $s->slug, 'color' => $s->color,
                    'is_initial' => $s->es_inicial, 'is_final' => $s->es_final, 'is_blocking' => $s->es_bloqueante,
                ])
                ->values(),
            'tasks' => $project->tasks()->orderBy('titulo')->get(['id', 'codigo', 'titulo'])
                ->map(fn ($t) => ['id' => $t->id, 'code' => $t->codigo ?? '#'.$t->id, 'title' => $t->titulo])
                ->values(),
        ]);
    }
}
