<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskDependency\StoreTaskDependencyRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskDependencyController extends Controller
{
    public function store(StoreTaskDependencyRequest $request, Task $task): RedirectResponse
    {
        $task->dependencies()->attach($request->validated('depends_on_task_id'));

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Dependencia agregada.');
    }

    public function destroy(Task $task, Task $dependency): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->dependencies()->detach($dependency->id);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Dependencia eliminada.');
    }
}
