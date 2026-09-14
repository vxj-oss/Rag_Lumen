<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Enums\RoleName;

class ProjectPolicy
{

    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            RoleName::Administrator->value,
            RoleName::Manager->value,
            RoleName::ProjectLead->value,
        ]);
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Manager->value])) {
            return true;
        }

        if ($this->isResponsibleFor($user, $project)) {
            return true;
        }

        $employeeId = $user->employee?->id;

        if ($employeeId === null) {
            return false;
        }

        if ($project->tasks()->where('assigned_to', $employeeId)->exists()) {
            return true;
        }

        return $project->members()
            ->wherePivot('status', 'active')
            ->where('employees.id', $employeeId)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $user->hasRole(RoleName::ProjectLead->value)
            && $this->isResponsibleFor($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    private function isResponsibleFor(User $user, Project $project): bool
    {
        return $project->responsible_employee_id !== null
            && $user->employee?->id === $project->responsible_employee_id;
    }
}
