<?php

namespace App\Http\Controllers\Web\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function __invoke(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $data = $request->validate(['comment' => ['required', 'string', 'max:450']]);
        ActivityLogger::record($task, 'commented', $data['comment']);

        return redirect()->route('tasks.show', $task->tarea_padre_id ?? $task->id)->with('status', 'Comentario registrado en el historial.');
    }
}
