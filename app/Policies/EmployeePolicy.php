<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\RoleName;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            RoleName::Administrator->value,
            RoleName::Manager->value,
            RoleName::ProjectLead->value,
        ]);
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        if ($user->employee?->id === $employee->id) {
            return true;
        }

        return $user->isLeader() && \App\Support\WorkspaceScope::employees($user)->whereKey($employee->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isLeader();
    }

    public function update(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ($user->isManager() || $user->isLeader())
            && (!$employee->user || $employee->user->hasExactRoles(['employee']))
            && $this->view($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    private function sharesProject(User $user, Employee $employee): bool
    {
        $leaderEmployeeId = $user->employee?->id;

        if ($leaderEmployeeId === null) {
            return false;
        }

        $ledProjectIds = Project::where('empleado_responsable_id', $leaderEmployeeId)->pluck('id');

        if ($ledProjectIds->isEmpty()) {
            return false;
        }

        return ProjectMember::whereIn('proyecto_id', $ledProjectIds)
            ->where('empleado_id', $employee->id)
            ->where('estado', 'active')
            ->exists()
            || Task::whereIn('proyecto_id', $ledProjectIds)
                ->where('asignado_a', $employee->id)
                ->exists();
    }
}
