<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskProgressUpdate;
use App\Services\EmployeeMetricsService;
use App\Services\ProjectDecisionService;
use App\Services\ProjectMetricsService;
use App\Support\Enums\DecisionSignal;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\RiskLevel;
use App\Support\Enums\TaskStatus;
use App\Support\ProjectScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const ACTIVE_TASK_STATUSES = ['pending', 'in_progress', 'review', 'blocked'];

    public function index(ProjectDecisionService $decisionService): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return view('dashboard.html.index', $this->buildDashboardData($decisionService));
        }

        if ($user->isManager()) {
            return view('dashboard.html.manager', $this->buildManagerData($decisionService));
        }

        if ($user->isLeader()) {
            return view('dashboard.html.leader', $this->buildLeaderData($decisionService));
        }

        return view('dashboard.html.employee', $this->buildEmployeeData());
    }

    public function export(ProjectDecisionService $decisionService): Response
    {
        $user = Auth::user();

        if ($user->isManager()) {
            $data = $this->buildManagerData($decisionService);
            $data['generatedAt'] = now();

            return Pdf::loadView('reports.dashboard-manager', $data)
                ->setPaper('a4', 'portrait')
                ->download('reporte-gerencia-'.now()->format('Y-m-d').'.pdf');
        }

        if ($user->isLeader()) {
            $data = $this->buildLeaderData($decisionService);
            $data['generatedAt'] = now();

            return Pdf::loadView('reports.dashboard-leader', $data)
                ->setPaper('a4', 'portrait')
                ->download('reporte-lider-'.now()->format('Y-m-d').'.pdf');
        }

        if (! $user->isAdmin()) {
            $data = $this->buildEmployeeData();
            $data['generatedAt'] = now();

            return Pdf::loadView('reports.dashboard-employee', $data)
                ->setPaper('a4', 'portrait')
                ->download('reporte-mi-dia-'.now()->format('Y-m-d').'.pdf');
        }

        $this->authorize('viewAny', Project::class);

        $data = $this->buildDashboardData($decisionService);
        $data['generatedAt'] = now();

        return Pdf::loadView('reports.dashboard', $data)
            ->setPaper('a4', 'portrait')
            ->download('reporte-dashboard-'.now()->format('Y-m-d').'.pdf');
    }

    private function scopedProjects()
    {
        return ProjectScope::apply(
            Project::with(['tasks.state', 'tasks.assignee.area', 'members', 'client']),
            Auth::user()
        )->get();
    }

    private function buildManagerData(ProjectDecisionService $decisionService): array
    {
        $projects = $this->scopedProjects();
        $metricsService = app(ProjectMetricsService::class);

        $rows = $projects->map(function (Project $project) use ($decisionService, $metricsService) {
            $metrics = $metricsService->forProject($project);
            $decision = $decisionService->evaluate($project);

            return [
                'project' => $project,
                'metrics' => $metrics,
                'decision' => $decision,
            ];
        });

        $tasks = $projects->flatMap->tasks;

        return [
            'summary' => [
                'scope_projects' => $projects->count(),
                'at_risk_projects' => $rows->filter(fn ($row) => in_array($row['decision']['risk_level'], [RiskLevel::High, RiskLevel::Critical], true))->count(),
                'delayed_projects' => $rows->filter(fn ($row) => in_array(DecisionSignal::Delayed, $row['decision']['signals'], true))->count(),
                'attention_projects' => $rows->filter(fn ($row) => ! in_array(DecisionSignal::Healthy, $row['decision']['signals'], true))->count(),
                'total_budget' => (float) $projects->sum('budget'),
                'overdue_tasks' => $tasks->filter(fn (Task $task) => $task->isOverdue())->count(),
            ],
            'rows' => $rows->sortByDesc(fn ($row) => $row['decision']['risk_score'])->values(),
            'areaPerformance' => $this->areaPerformance($tasks),
            'riskByProject' => $rows->take(10)->mapWithKeys(fn ($row) => [$row['project']->name => $row['decision']['risk_score']]),
            'progressByProject' => $rows->take(10)->mapWithKeys(fn ($row) => [$row['project']->name => $row['metrics']['real_progress']]),
        ];
    }

    private function areaPerformance($tasks): array
    {
        return Area::where('active', true)->orderBy('name')->get()->map(function (Area $area) use ($tasks) {
            $areaTasks = $tasks->filter(fn (Task $task) => $task->assignee?->area_id === $area->id);

            return [
                'area' => $area,
                'total' => $areaTasks->count(),
                'completed' => $areaTasks->filter(fn (Task $task) => $task->isCompletedState())->count(),
                'active' => $areaTasks->filter(fn (Task $task) => $task->isActiveState())->count(),
                'overdue' => $areaTasks->filter(fn (Task $task) => $task->isOverdue())->count(),
                'employees' => $areaTasks->pluck('assigned_to')->filter()->unique()->count(),
            ];
        })->filter(fn ($row) => $row['total'] > 0)->values()->all();
    }

    private function buildLeaderData(ProjectDecisionService $decisionService): array
    {
        $employeeId = Auth::user()->employee?->id;

        $projects = Project::with(['tasks.state', 'tasks.assignee', 'members'])
            ->where('responsible_employee_id', $employeeId)
            ->get();

        $metricsService = app(ProjectMetricsService::class);
        $employeeMetrics = app(EmployeeMetricsService::class);

        $rows = $projects->map(fn (Project $project) => [
            'project' => $project,
            'metrics' => $metricsService->forProject($project),
            'decision' => $decisionService->evaluate($project),
        ]);

        $tasks = $projects->flatMap->tasks;

        $memberIds = $projects->flatMap(fn (Project $project) => $project->members->where('pivot.status', 'active')->pluck('id'))
            ->merge($tasks->pluck('assigned_to')->filter())
            ->unique()->values();

        $teamLoad = Employee::whereIn('id', $memberIds)->orderBy('first_name')->get()
            ->map(fn (Employee $employee) => [
                'employee' => $employee,
                'metrics' => $employeeMetrics->forEmployee($employee),
            ]);

        return [
            'summary' => [
                'my_projects' => $projects->count(),
                'overdue_tasks' => $tasks->filter(fn (Task $task) => $task->isOverdue())->count(),
                'blocked_tasks' => $tasks->filter(fn (Task $task) => $task->isBlockingState())->count(),
                'due_soon_tasks' => $tasks->filter(fn (Task $task) => ! $task->isFinalState()
                    && $task->due_date !== null
                    && $task->due_date->between(now()->startOfDay(), now()->addDays(3)->endOfDay()))->count(),
            ],
            'rows' => $rows->sortByDesc(fn ($row) => $row['decision']['risk_score'])->values(),
            'overdueTasks' => $tasks->filter(fn (Task $task) => $task->isOverdue())
                ->sortBy('due_date')->take(10)->values(),
            'teamLoad' => $teamLoad,
        ];
    }

    private function buildEmployeeData(): array
    {
        $employeeId = Auth::user()->employee?->id;

        $tasks = Task::with(['state', 'project'])
            ->where('assigned_to', $employeeId)
            ->orderBy('due_date')
            ->get();

        return [
            'summary' => [
                'pending' => $tasks->filter(fn (Task $task) => $task->state?->slug === 'pendiente')->count(),
                'in_progress' => $tasks->filter(fn (Task $task) => $task->state?->slug === 'en-progreso')->count(),
                'due_soon' => $tasks->filter(fn (Task $task) => ! $task->isFinalState()
                    && $task->due_date !== null
                    && $task->due_date->between(now()->startOfDay(), now()->addDays(3)->endOfDay()))->count(),
                'blocked' => $tasks->filter(fn (Task $task) => $task->isBlockingState())->count(),
                'hours_recorded' => round((float) $tasks->sum('actual_hours'), 2),
            ],
            'byState' => $tasks->groupBy(fn (Task $task) => $task->state?->name ?? $task->status->label()),
            'upcoming' => $tasks->filter(fn (Task $task) => ! $task->isFinalState() && $task->due_date !== null)
                ->sortBy('due_date')->take(8)->values(),
        ];
    }

    private function buildDashboardData(ProjectDecisionService $decisionService): array
    {
        $projects = Project::with(['tasks', 'members'])->get();

        $decisions = $projects->mapWithKeys(fn (Project $project) => [
            $project->id => $decisionService->evaluate($project),
        ]);

        $hasSignal = fn (DecisionSignal $signal) => $decisions->filter(
            fn ($decision) => in_array($signal, $decision['signals'], true)
        );

        $attentionProjects = $decisions->filter(
            fn ($decision) => ! in_array(DecisionSignal::Healthy, $decision['signals'], true)
        );

        $summary = [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->whereNotIn('status', [ProjectStatus::Completed, ProjectStatus::Cancelled])->count(),
            'delayed_projects' => $hasSignal(DecisionSignal::Delayed)->count(),
            'critical_projects' => $decisions->filter(fn ($d) => $d['risk_level'] === RiskLevel::Critical)->count(),
            'blocked_projects' => $hasSignal(DecisionSignal::Blocked)->count(),
            'attention_projects' => $attentionProjects->count(),
            'pending_tasks' => Task::where('status', TaskStatus::Pending->value)->count(),
            'overdue_tasks' => Task::whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'completed_tasks' => Task::where('status', TaskStatus::Completed->value)->count(),
            'overloaded_employees' => $this->overloadedEmployeesCount(),
        ];

        $projectsByStatus = $projects->groupBy(fn ($p) => $p->status->label())->map->count();
        $tasksByStatus = Task::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status) => [TaskStatus::from($status)->label() => $total]);

        $riskByProject = $projects->take(10)->mapWithKeys(fn ($p) => [$p->name => $decisions[$p->id]['risk_score']]);

        $metricsService = app(ProjectMetricsService::class);
        $progressByProject = $projects->take(10)->mapWithKeys(
            fn ($p) => [$p->name => $metricsService->forProject($p)['real_progress']]
        );

        $employeePerformance = Employee::withCount([
            'tasks as completed_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Completed->value),
        ])->orderByDesc('completed_tasks_count')->take(8)->get()
            ->mapWithKeys(fn ($e) => [$e->fullName() => $e->completed_tasks_count]);

        $progressEvolution = $this->weeklyProgressEvolution();

        $attentionList = $attentionProjects->map(function ($decision) use ($projects) {
            $project = $projects->firstWhere('id', $decision['project_id']);

            return [
                'project' => $project,
                'decision' => $decision,
            ];
        })->sortByDesc(fn ($item) => $item['decision']['risk_score'])->values();

        return [
            'summary' => $summary,
            'projectsByStatus' => $projectsByStatus,
            'tasksByStatus' => $tasksByStatus,
            'riskByProject' => $riskByProject,
            'progressByProject' => $progressByProject,
            'employeePerformance' => $employeePerformance,
            'progressEvolution' => $progressEvolution,
            'attentionList' => $attentionList,
        ];
    }

    private function overloadedEmployeesCount(): int
    {
        $maxRecommended = config('risk.employee_max_recommended_tasks');

        return Employee::withCount([
            'tasks as active_tasks_count' => fn ($q) => $q->whereIn('status', self::ACTIVE_TASK_STATUSES),
        ])->get()->filter(fn ($e) => $e->active_tasks_count > $maxRecommended)->count();
    }

    private function weeklyProgressEvolution(): array
    {
        $weeks = collect(range(7, 0))->map(fn ($weeksAgo) => now()->subWeeks($weeksAgo)->startOfWeek());

        $labels = $weeks->map(fn ($week) => $week->format('d/m'))->all();

        $values = $weeks->map(function ($weekStart) {
            $weekEnd = $weekStart->copy()->endOfWeek();

            return (int) TaskProgressUpdate::whereBetween('created_at', [$weekStart, $weekEnd])
                ->selectRaw('SUM(CAST(new_percentage AS SIGNED) - CAST(previous_percentage AS SIGNED)) as delta')
                ->value('delta');
        })->all();

        return ['labels' => $labels, 'values' => $values];
    }
}
