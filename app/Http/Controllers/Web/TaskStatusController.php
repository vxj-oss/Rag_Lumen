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

        $states = TaskState::where('proyecto_id', $project->id)->orderBy('posicion')->get();

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
            'proyecto_id' => $project->id,
            'nombre' => $data['name'],
            'slug' => TaskState::makeSlug($data['name'], $project->id),
            'color' => $data['color'],
            'posicion' => (int) (TaskState::where('proyecto_id', $project->id)->max('posicion') ?? 0) + 1,
            'es_inicial' => $request->boolean('is_initial'),
            'es_final' => $request->boolean('is_final'),
            'es_bloqueante' => $request->boolean('is_blocking'),
            'activo' => true,
        ]);

        ActivityLogger::record($project, 'created', "Agregó el estado \"{$state->nombre}\" al proyecto \"{$project->nombre}\".");

        return redirect()
            ->route('projects.statuses.index', $project)
            ->with('status', 'Estado creado correctamente.');
    }

    public function update(Request $request, Project $project, TaskState $status): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_if($status->proyecto_id !== $project->id, 404);

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
            'nombre' => $data['name'],
            'slug' => TaskState::makeSlug($data['name'], $project->id, $status->id),
            'color' => $data['color'],
            'es_inicial' => $request->boolean('is_initial'),
            'es_final' => $request->boolean('is_final'),
            'es_bloqueante' => $request->boolean('is_blocking'),
            'activo' => $request->boolean('active'),
        ]);

        if (! TaskState::where('proyecto_id', $project->id)->where('es_inicial', true)->exists()
            || ! TaskState::where('proyecto_id', $project->id)->where('es_final', true)->exists()) {
            $status->update($before);

            return redirect()
                ->route('projects.statuses.index', $project)
                ->with('toast_error', 'El proyecto debe conservar al menos un estado inicial y uno final.');
        }

        ActivityLogger::recordUpdate($status, $before, "el estado \"{$status->nombre}\" del proyecto \"{$project->nombre}\"");

        return redirect()
            ->route('projects.statuses.index', $project)
            ->with('status', 'Estado actualizado correctamente.');
    }

    public function destroy(Project $project, TaskState $status): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_if($status->proyecto_id !== $project->id, 404);

        if ($status->tasks()->exists()) {
            return redirect()
                ->route('projects.statuses.index', $project)
                ->with('toast_error', 'No se puede eliminar: hay tareas usando este estado.');
        }

        ActivityLogger::record($project, 'deleted', "Eliminó el estado \"{$status->nombre}\" del proyecto \"{$project->nombre}\".");

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
            'ordered_ids.*' => ['integer', Rule::exists('estados_tarea', 'id')->where('proyecto_id', $project->id)],
        ]);

        foreach (array_values($data['ordered_ids']) as $position => $id) {
            TaskState::where('id', $id)->update(['posicion' => $position + 1]);
        }

        return redirect()
            ->route('projects.statuses.index', $project)
            ->with('status', 'Orden actualizado correctamente.');
    }
}
