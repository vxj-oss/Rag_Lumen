<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Services\ProjectDecisionService;
use App\Services\ProjectMetricsService;
use App\Services\ProjectRiskService;
use App\Support\ActivityLogger;
use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectRiskService $riskService): View
    {
        $this->authorize('viewAny', Project::class);

        $allProjects = Project::with(['tasks', 'members'])->get();
        $risksById = $allProjects->mapWithKeys(fn (Project $project) => [
            $project->id => $riskService->calculate($project),
        ]);
        $highRiskCount = $risksById->filter(fn ($risk) => in_array($risk['level']->value, ['high', 'critical'], true))->count();

        $projects = Project::query()
            ->with('responsibleEmployee')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
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
            'filters' => $request->only(['search', 'status', 'priority']),
        ] + $this->formOptions());
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

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

        $project->load(['responsibleEmployee', 'tasks', 'members' => function ($query) {
            $query->orderByPivot('status')->orderBy('first_name');
        }]);

        $availableEmployees = Employee::whereNotIn('id', $project->members->pluck('id'))
            ->orderBy('first_name')
            ->get();

        return view('projects.html.show', [
            'project' => $project,
            'availableEmployees' => $availableEmployees,
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

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
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

        $project->update($request->validated());

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
        ];
    }
}
