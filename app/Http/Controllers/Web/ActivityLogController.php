<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Enums\RoleName;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    private const ACTIONS = ['created', 'updated', 'deleted', 'progress_recorded'];

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()->hasRole([RoleName::Administrator->value, RoleName::Manager->value]),
            403
        );

        $logs = ActivityLog::with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
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
