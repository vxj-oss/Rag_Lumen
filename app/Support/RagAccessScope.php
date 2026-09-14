<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use App\Support\Enums\RoleName;

class RagAccessScope
{
    
    public static function accessibleProjectIds(User $user): ?array
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Manager->value])) {
            return null;
        }

        $employeeId = $user->employee?->id;

        if ($employeeId === null) {
            return [];
        }

        return Project::query()
            ->where('responsible_employee_id', $employeeId)
            ->orWhereHas('members', function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId)
                    ->where('project_members.status', 'active');
            })
            ->pluck('id')
            ->all();
    }
}
