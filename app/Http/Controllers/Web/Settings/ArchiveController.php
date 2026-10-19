<?php

namespace App\Http\Controllers\Web\Settings;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Support\ActivityLogger;
use App\Support\TaskProgressRollup;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    private function model(string $kind): string
    {
        return match ($kind) {
            'employees' => Employee::class, 'projects' => Project::class, 'tasks' => Task::class, default => abort(404)
        };
    }

    public function index(Request $request)
    {
        $kind = $request->query('kind', 'projects');
        $class = $this->model($kind);

        return view('settings.archive', ['kind' => $kind, 'records' => $class::onlyTrashed()->latest('deleted_at')->paginate(\App\Support\ListPagination::size())->withQueryString()]);
    }

    public function restore(string $kind, int $id)
    {
        $class = $this->model($kind);
        $record = $class::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $record);
        if ($record instanceof Task) {
            abort_unless($record->project, 422, 'Primero restaura el proyecto de esta actividad.');
            if ($record->tarea_padre_id) {
                abort_unless($record->tareaPadre && $record->tareaPadre->admiteCambiosDeTareas(), 422, 'Primero restaura la actividad y comprueba que admite cambios de tareas.');
            }
        }
        $record->restore();
        if ($record instanceof Task) {
            TaskProgressRollup::recalculateAncestors($record, auth()->user());
        }
        ActivityLogger::record($record, 'restored', 'Restauró el registro desde el archivo recuperable.');

        return back()->with('status', 'Registro restaurado. Las cuentas de acceso se administran por separado.');
    }
}
