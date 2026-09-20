<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DatabaseNotification::class);

        $notifications = auth()->user()->notifications()->paginate(5);

        return view('alerts.html.index', [
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
