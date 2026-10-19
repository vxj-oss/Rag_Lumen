<?php

namespace App\Services;

use App\Models\User;
use App\Support\Enums\DecisionSignal;
use App\Support\Enums\RiskLevel;
use App\Support\WorkspaceScope;

class WorkspaceAttentionService
{
    public function forUser(User $user): array
    {
        if ($user->isAdmin() || $user->isManager()) {
            return WorkspaceScope::projects($user)->with(['tasks.state', 'members'])->get()->map(function ($project) use ($user) {
                $decision = app(ProjectDecisionService::class)->evaluate($project);
                $attention = $user->isAdmin()
                    ? ! in_array(DecisionSignal::Healthy, $decision['signals'], true)
                    : in_array($decision['risk_level'], [RiskLevel::High, RiskLevel::Critical], true);

                return $attention ? ['title' => $project->nombre, 'reason' => implode(' ', $decision['reasons']), 'url' => route('projects.show', $project)] : null;
            })->filter()->values()->all();
        }
        $query = WorkspaceScope::tasks($user)->activities()->with(['state', 'project']);
        if ($user->isLeader()) {
            $query->whereHas('project', fn ($q) => $q->where('empleado_responsable_id', $user->employee?->id));
        } else {
            $query->where('asignado_a', $user->employee?->id ?? 0);
        }

        return $query->get()->filter(fn ($task) => $user->isLeader() ? $task->isBlockingState() : ! $task->isFinalState() && $task->fecha_vencimiento?->between(now()->startOfDay(), now()->addDays(3)->endOfDay()))
            ->map(fn ($task) => ['title' => $task->titulo, 'reason' => $user->isLeader() ? 'Actividad bloqueada.' : 'Vence el '.$task->fecha_vencimiento->format('d/m/Y').'.', 'url' => route('tasks.show', $task)])->values()->all();
    }
}
