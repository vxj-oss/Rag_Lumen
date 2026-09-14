<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskProgressUpdate;
use App\Services\ProjectDecisionService;
use App\Services\ProjectMetricsService;
use App\Support\Enums\DecisionSignal;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\RiskLevel;
use App\Support\Enums\TaskStatus;
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
        if (Auth::user()->isPlainEmployee()) {
            return redirect()->route('tasks.index');
        }

        return view('dashboard.html.index', $this->buildDashboardData($decisionService));
    }

    public function export(ProjectDecisionService $decisionService): Response
    {
        $this->authorize('viewAny', Project::class);

        $data = $this->buildDashboardData($decisionService);
        $data['generatedAt'] = now();

        return Pdf::loadView('reports.dashboard', $data)
            ->setPaper('a4', 'portrait')
            ->download('reporte-dashboard-'.now()->format('Y-m-d').'.pdf');
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
