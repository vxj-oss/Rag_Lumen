<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TaskState;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskStatusController extends Controller
{
    public function index(Project $project): View
    {
        $this->authorize('update', $project);

        $states = TaskState::where('project_id', $project->id)->orderBy('position')->get();

        return view('projects.html.statuses', ['project' => $project, 'states' => $states]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', Rule::in(['gray', 'blue', 'indigo', 'green', 'yellow', 'red', 'orange', 'amber'])],
            'is_initial' => ['sometimes', 'boolean'],
            'is_final' => ['sometimes', 'boolean'],
            'is_blocking' => ['sometimes', 'boolean'],
        ]);

        $state = TaskState::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'slug' => TaskState::makeSlug($data['name'], $project->id),
            'color' => $data['color'],
            'position' => (int) (TaskState::where('project_id', $project->id)->max('position') ?? 0) + 1,
            'is_initial' => $request->boolean('is_initial'),
            'is_final' => $request->boolean('is_final'),
            'is_blocking' => $request->boolean('is_blocking'),
            'active' => true,
        ]);

        ActivityLogger::record($project, 'created', "Agregó el estado \"{$state->name}\" al proyecto \"{$project->name}\".");

        return redirect()
            ->route('projects.statuses.index', $project)
            ->with('status', 'Estado creado correctamente.');
    }

    public function update(Request $request, Project $project, TaskState $status): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_if($status->project_id !== $project->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', Rule::in(['gray', 'blue', 'indigo', 'green', 'yellow', 'red', 'orange', 'amber'])],
            'is_initial' => ['sometimes', 'boolean'],
            'is_final' => ['sometimes', 'boolean'],
            'is_blocking' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $before = $status->getAttributes();

        $status->update([
            'name' => $data['name'],
            'slug' => TaskState::makeSlug($data['name'], $project->id, $status->id),
            'color' => $data['color'],
            'is_initial' => $request->boolean('is_initial'),
            'is_final' => $request->boolean('is_final'),
            'is_blocking' => $request->boolean('is_blocking'),
            'active' => $request->boolean('active'),
        ]);

        if (! TaskState::where('project_id', $project->id)->where('is_initial', true)->exists()
            || ! TaskState::where('project_id', $project->id)->where('is_final', true)->exists()) {
            $status->update($before);

            return redirect()
                ->route('projects.statuses.index', $project)
                ->with('toast_error', 'El proyecto debe conservar al menos un estado inicial y uno final.');
        }

        ActivityLogger::recordUpdate($status, $before, "el estado \"{$status->name}\" del proyecto \"{$project->name}\"");

        return redirect()
            ->route('projects.statuses.index', $project)
            ->with('status', 'Estado actualizado correctamente.');
    }

    public function destroy(Project $project, TaskState $status): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_if($status->project_id !== $project->id, 404);

        if ($status->tasks()->exists()) {
            return redirect()
                ->route('projects.statuses.index', $project)
                ->with('toast_error', 'No se puede eliminar: hay tareas usando este estado.');
        }

        ActivityLogger::record($project, 'deleted', "Eliminó el estado \"{$status->name}\" del proyecto \"{$project->name}\".");

        $status->delete();

        return redirect()
            ->route('projects.statuses.index', $project)
            ->with('status', 'Estado eliminado correctamente.');
    }

    public function reorder(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', Rule::exists('task_statuses', 'id')->where('project_id', $project->id)],
        ]);

        foreach (array_values($data['ordered_ids']) as $position => $id) {
            TaskState::where('id', $id)->update(['position' => $position + 1]);
        }

        return redirect()
            ->route('projects.statuses.index', $project)
            ->with('status', 'Orden actualizado correctamente.');
    }
}
