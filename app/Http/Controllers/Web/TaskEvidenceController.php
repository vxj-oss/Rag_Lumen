<?php

namespace App\Http\Controllers\Web;

use App\Actions\ReviewTaskEvidenceAction;
use App\Actions\SubmitTaskEvidenceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskEvidence\RejectTaskEvidenceRequest;
use App\Http\Requests\TaskEvidence\StoreTaskEvidenceRequest;
use App\Models\Task;
use App\Models\TaskEvidence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TaskEvidenceController extends Controller
{
    public function store(StoreTaskEvidenceRequest $request, Task $task, SubmitTaskEvidenceAction $action): RedirectResponse
    {
        $action->execute(
            $task,
            $request->file('archivos', []),
            $request->input('enlaces', []),
            $request->validated('comentario'),
            $request->user(),
        );

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Tarea completada con evidencia.');
    }

    public function approve(Task $task, ReviewTaskEvidenceAction $action): RedirectResponse
    {
        $this->authorize('reviewEvidence', $task);
        abort_unless($task->enRevision(), 422, 'Esta actividad no está pendiente de revisión.');

        $action->approve($task, request()->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Actividad aprobada.');
    }

    public function reject(RejectTaskEvidenceRequest $request, Task $task, ReviewTaskEvidenceAction $action): RedirectResponse
    {
        $action->reject($task, $request->user(), $request->validated('motivo'));

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Actividad rechazada. El trabajador debe corregir y volver a presentar la evidencia.');
    }

    public function download(Task $task, TaskEvidence $evidence): Response
    {
        $this->authorize('view', $task);
        abort_unless($evidence->tarea_id === $task->id, 404);
        abort_unless($evidence->esArchivo() && Storage::disk('local')->exists($evidence->ruta_archivo), 404);

        return response(Storage::disk('local')->get($evidence->ruta_archivo), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($evidence->ruta_archivo),
            'Content-Disposition' => 'attachment; filename="'.$evidence->nombre_archivo.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
