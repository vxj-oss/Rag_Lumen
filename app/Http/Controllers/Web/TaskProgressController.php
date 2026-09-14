<?php

namespace App\Http\Controllers\Web;

use App\Actions\RecordTaskProgressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskProgressUpdate\StoreTaskProgressUpdateRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskProgressController extends Controller
{
    public function store(StoreTaskProgressUpdateRequest $request, Task $task, RecordTaskProgressAction $action): RedirectResponse
    {
        $action->execute(
            $task,
            (int) $request->validated('new_percentage'),
            $request->validated('comment'),
            $request->user(),
        );

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Avance registrado correctamente.');
    }
}
