<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Enums\RoleName;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            RoleName::Administrator->value,
            RoleName::Manager->value,
            RoleName::ProjectLead->value,
            RoleName::Employee->value,
        ]);
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Manager->value])) {
            return true;
        }

        return $this->leadsProject($user, $task) || $this->isAssignee($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->hasRole([RoleName::Administrator->value, RoleName::ProjectLead->value]);
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $this->leadsProject($user, $task) || $this->isAssignee($user, $task);
    }


    public function delete(User $user, Task $task): bool
    {
        return $user->hasRole(RoleName::Administrator->value) || $this->leadsProject($user, $task);
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    private function leadsProject(User $user, Task $task): bool
    {
        return $user->hasRole(RoleName::ProjectLead->value)
            && $task->project->responsible_employee_id !== null
            && $user->employee?->id === $task->project->responsible_employee_id;
    }

    private function isAssignee(User $user, Task $task): bool
    {
        return $task->assigned_to !== null && $user->employee?->id === $task->assigned_to;
    }
}
