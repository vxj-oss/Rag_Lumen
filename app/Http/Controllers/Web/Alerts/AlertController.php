<?php

namespace App\Http\Controllers\Web\Alerts;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WorkspaceAttentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function preferences(Request $request): RedirectResponse
    {
        $data = $request->validate(['notify_in_app' => ['required', 'boolean'], 'notify_by_email' => ['required', 'boolean']]);
        $request->user()->update($data);

        return back()->with('status', 'Preferencias de notificación actualizadas.');
    }

    public function index(): View
    {
        $this->authorize('viewAny', DatabaseNotification::class);

        $notifications = auth()->user()->notifications()->paginate(\App\Support\ListPagination::size());

        return view('alerts.index', [
            'attentionItems' => app(WorkspaceAttentionService::class)->forUser(auth()->user()),
            'notifications' => $notifications,
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json(['count' => auth()->user()->unreadNotifications()->count()]);
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        abort_if(
            $notification->notifiable_id !== auth()->id() || $notification->notifiable_type !== User::class,
            403
        );

        $notification->markAsRead();

        return back()->with('status', 'Alerta marcada como leída.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Todas las alertas fueron marcadas como leídas.');
    }
}
