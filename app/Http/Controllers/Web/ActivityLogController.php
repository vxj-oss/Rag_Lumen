<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\RoleName;
use App\Support\ProjectScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    private const ACTIONS = ['created', 'updated', 'deleted', 'progress_recorded'];

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole([RoleName::Administrator->value, RoleName::Manager->value])
                || $user->isLeader(),
            403
        );

        $logs = ActivityLog::with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($user->isLeader() && ! $user->isAdmin() && ! $user->isManager(), function ($query) use ($user) {
                $projectIds = ProjectScope::accessibleProjectIds($user) ?? [];

                $query->where(function ($query) use ($projectIds) {
                    $query->where(function ($query) use ($projectIds) {
                        $query->where('subject_type', Project::class)
                            ->whereIn('subject_id', $projectIds);
                    })->orWhere(function ($query) use ($projectIds) {
                        $query->where('subject_type', Task::class)
                            ->whereIn('subject_id', Task::whereIn('project_id', $projectIds)->select('id'));
                    });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('activity.html.index', [
            'logs' => $logs,
            'actions' => self::ACTIONS,
            'filters' => $request->only(['action']),
        ]);
    }
}
