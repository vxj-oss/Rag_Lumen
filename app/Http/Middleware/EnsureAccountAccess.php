<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! $user = $request->user()) {
            return $next($request);
        }
        $version = (int) $user->auth_version;
        $sessionVersion = (int) $request->session()->get('auth_version', 1);
        if (! $user->is_active || $sessionVersion !== $version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu acceso cambió. Inicia sesión nuevamente.'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => 'Tu sesión finalizó o tu cuenta está desactivada.']);
        }
        $request->session()->put('auth_version', $version);

        if ($user->isEmployee()) {
            if ($request->routeIs('dashboard', 'dashboard.export')) {
                return redirect()->route('rag-query.index');
            }
            abort_if($request->routeIs('projects.*', 'clients.*', 'areas.*', 'activity.*', 'alerts.*', 'profile.update', 'profile.destroy', 'employees.normativa.aceptar'), 403);
        }

        return $next($request);
    }
}
