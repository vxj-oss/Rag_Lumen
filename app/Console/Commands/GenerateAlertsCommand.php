<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\EmployeeOverloadedNotification;
use App\Notifications\ProjectDelayedNotification;
use App\Notifications\ProjectHighRiskNotification;
use App\Notifications\TaskDueSoonNotification;
use App\Services\ProjectDecisionService;
use App\Support\Enums\DecisionSignal;
use App\Support\Enums\RiskLevel;
use App\Support\Enums\RoleName;
use Illuminate\Console\Command;

class GenerateAlertsCommand extends Command
{
    protected $signature = 'alerts:generate';

    protected $description = 'Scan projects, employees and tasks and generate alerts for conditions that need attention';

    private const DUE_SOON_DAYS = 3;

    private const DUE_SOON_PROGRESS_CEILING = 70;

    public function handle(ProjectDecisionService $decisionService): int
    {
        $managers = User::role([RoleName::Administrator->value, RoleName::Manager->value])->get();
        $created = 0;

        $created += $this->generateProjectAlerts($decisionService, $managers);
        $created += $this->generateEmployeeOverloadAlerts($managers);
        $created += $this->generateTaskDueSoonAlerts();

        $this->info("Alertas generadas: {$created}");

        return self::SUCCESS;
    }

    private function generateProjectAlerts(ProjectDecisionService $decisionService, $managers): int
    {
        $created = 0;

        foreach (Project::with(['tasks', 'members', 'responsibleEmployee.user'])->get() as $project) {
            $decision = $decisionService->evaluate($project);

            $recipients = $managers;

            if ($project->responsibleEmployee?->user) {
                $recipients = $recipients->push($project->responsibleEmployee->user)->unique('id');
            }

            if ($decision['risk_level'] === RiskLevel::Critical) {
                $created += $this->notifyUnlessDuplicate(
                    $recipients,
                    ProjectHighRiskNotification::class,
                    ['project_id' => $project->id],
                    fn ($user) => $user->notify(new ProjectHighRiskNotification($project))
                );
            }

            if (in_array(DecisionSignal::Delayed, $decision['signals'], true)) {
                $overdueTasks = $project->tasks->filter(fn ($task) => $task->isOverdue())->count();

                $created += $this->notifyUnlessDuplicate(
                    $recipients,
                    ProjectDelayedNotification::class,
                    ['project_id' => $project->id],
                    fn ($user) => $user->notify(new ProjectDelayedNotification($project, $overdueTasks))
                );
            }
        }

        return $created;
    }

    private function generateEmployeeOverloadAlerts($managers): int
    {
        $created = 0;
        $maxRecommended = config('risk.employee_max_recommended_tasks');

        $employees = Employee::with('tasks.state')->get()->filter(function ($e) use ($maxRecommended) {
            $e->setAttribute(
                'active_tasks_count',
                $e->tasks->filter(fn ($task) => $task->isActiveState())->count()
            );

            return $e->active_tasks_count > $maxRecommended;
        });

        foreach ($employees as $employee) {
            $created += $this->notifyUnlessDuplicate(
                $managers,
                EmployeeOverloadedNotification::class,
                ['employee_id' => $employee->id],
                fn ($user) => $user->notify(new EmployeeOverloadedNotification($employee, $employee->active_tasks_count))
            );
        }

        return $created;
    }

    private function generateTaskDueSoonAlerts(): int
    {
        $created = 0;

        $tasks = Task::with(['assignee.user', 'state'])
            ->where('porcentaje_progreso', '<', self::DUE_SOON_PROGRESS_CEILING)
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [now()->startOfDay(), now()->addDays(self::DUE_SOON_DAYS)->endOfDay()])
            ->get()
            ->filter(fn (Task $task) => $task->isActiveState());

        foreach ($tasks as $task) {
            $user = $task->assignee?->user;

            if (! $user) {
                continue;
            }

            $daysRemaining = (int) round(now()->startOfDay()->diffInDays($task->fecha_vencimiento, false));

            $created += $this->notifyUnlessDuplicate(
                collect([$user]),
                TaskDueSoonNotification::class,
                ['task_id' => $task->id],
                fn ($u) => $u->notify(new TaskDueSoonNotification($task, max(0, $daysRemaining)))
            );
        }

        return $created;
    }

    private function notifyUnlessDuplicate($recipients, string $notificationClass, array $matchData, \Closure $send): int
    {
        $created = 0;

        foreach ($recipients as $user) {
            $exists = $user->notifications()
                ->where('type', $notificationClass)
                ->whereNull('read_at')
                ->where(function ($query) use ($matchData) {
                    foreach ($matchData as $key => $value) {
                        $query->where("data->{$key}", $value);
                    }
                })
                ->exists();

            if (! $exists) {
                $send($user);
                $created++;
            }
        }

        return $created;
    }
}
