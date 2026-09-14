<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Support\Enums\RoleName;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([RoleName::Administrator->value, RoleName::Manager->value]);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasRole([RoleName::Administrator->value, RoleName::Manager->value]);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
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
}
