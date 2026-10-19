<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class WorkspaceScope
{
    public static function projects(User $user): Builder
    {
        return ProjectScope::apply(Project::query(), $user);
    }

    public static function tasks(User $user): Builder
    {
        $query = Task::query()->whereHas('project');
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }
        $id = $user->employee?->id;
        if ($id === null) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isManager()) {
            return $query->whereIn('proyecto_id', ProjectScope::accessibleProjectIds($user) ?? []);
        }

        return $query->where(function ($q) use ($user, $id) {
            $q->where('asignado_a', $id);
            if ($user->isLeader()) {
                $q->orWhereIn('proyecto_id', ProjectScope::accessibleProjectIds($user) ?? []);
            }
        });
    }

    public static function employees(User $user): Builder
    {
        $query = Employee::query();
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }
        $id = $user->employee?->id;
        if ($id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($user, $id) {
            $q->where('id', $id);
            if ($user->isLeader()) {
                $specialties = collect(array_keys(WorkspaceAccess::areasFor($user)))->flatMap(fn ($area) => WorkspaceAccess::especialidades($area))->all();
                $q->orWhere(fn ($unassigned) => $unassigned->whereNull('area_id')->whereIn('especialidad', $specialties));
                $q->orWhereIn('area_id', WorkspaceAccess::areaIdsFor($user) ?? []);
            }
        });
    }
}
