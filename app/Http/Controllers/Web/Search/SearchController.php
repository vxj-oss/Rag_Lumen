<?php

namespace App\Http\Controllers\Web\Search;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Support\ProjectScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $user = $request->user();
        $term = '%'.$data['q'].'%';
        $results = collect();

        if ($user->can('viewAny', Project::class)) {
            $projects = ProjectScope::apply(Project::query(), $user)
                ->where(fn ($q) => $q->where('nombre', 'like', $term)->orWhere('codigo', 'like', $term))
                ->orderBy('nombre')->limit(8)->get();
            foreach ($projects as $project) {
                if ($user->can('view', $project)) {
                    $results->push(['type' => 'Proyecto', 'title' => $project->nombre, 'detail' => $project->codigo, 'url' => route('projects.show', $project)]);
                }
            }
        }

        if ($user->can('viewAny', Task::class)) {
            $tasks = \App\Support\WorkspaceScope::tasks($user)->with('project')->where(fn ($q) => $q->where('titulo', 'like', $term)->orWhere('codigo','like',$term));
            foreach ($tasks->orderBy('titulo')->limit(8)->get() as $task) {
                if ($user->can('view', $task)) {
                    $results->push(['type' => 'Actividad', 'title' => $task->titulo, 'detail' => $task->codigo, 'url' => route('tasks.show', $task)]);
                }
            }
        }

        if ($user->can('viewAny', Employee::class)) {
            $employees = \App\Support\WorkspaceScope::employees($user)->where(fn ($q) => $q->where('nombres','like',$term)->orWhere('apellidos','like',$term));
            foreach ($employees->orderBy('nombres')->limit(8)->get() as $employee) {
                if ($user->can('view', $employee)) {
                    $results->push(['type' => 'Empleado', 'title' => $employee->fullName(), 'detail' => $employee->cargo, 'url' => route('employees.show', $employee)]);
                }
            }
        }

        return response()->json(['results' => $results->values()])->header('Cache-Control', 'no-store');
    }
}
