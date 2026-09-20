<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProjectScope
{
    /**
     * IDs de proyectos visibles para el usuario.
     * null = alcance global (sin restricción).
     */
    public static function accessibleProjectIds(User $user): ?array
    {
        if ($user->isAdmin()) {
            return null;
        }

        $employeeId = $user->employee?->id;

        if ($employeeId === null) {
            return [];
        }

        if ($user->isManager()) {
            return Project::query()
                ->where('manager_employee_id', $employeeId)
                ->orWhereHas('areas', function ($query) use ($employeeId) {
                    $query->whereIn('areas.id', function ($query) use ($employeeId) {
                        $query->select('area_id')->from('employees')->where('employees.id', $employeeId);
                    });
                })
                ->pluck('id')
                ->all();
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

    /**
     * Aplica el alcance a una query de proyectos (o con columna project_id).
     */
    public static function apply(Builder $query, User $user, string $column = 'id'): Builder
    {
        $ids = self::accessibleProjectIds($user);

        if ($ids === null) {
            return $query;
        }

        return $query->whereIn($column, $ids);
    }
}
