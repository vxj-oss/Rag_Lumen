<?php

namespace App\Services\Assistant;

use App\Models\ActivityLog;
use App\Models\Falta;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\ProjectScope;
use App\Support\WorkspaceAccess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdministrativeQuery
{
    public function answer(string $question, User $user): ?array
    {
        $q = Str::lower(Str::ascii($question));
        if (preg_match('/\b(auditoria|historial de cambios|actividad del sistema)\b/', $q)) {
            if ($user->isEmployee()) {
                return ['answer' => 'Tu rol no tiene acceso al registro de auditoría.', 'sources' => []];
            }
            $query = ActivityLog::query()->with('user');
            if (! $user->isAdmin() && ! $user->isManager()) {
                $ids = ProjectScope::accessibleProjectIds($user) ?? [];
                $query->where(fn ($log) => $log->where(fn ($project) => $project->where('sujeto_tipo', Project::class)->whereIn('sujeto_id', $ids))
                    ->orWhere(fn ($task) => $task->where('sujeto_tipo', Task::class)->whereIn('sujeto_id', Task::whereIn('proyecto_id', $ids)->select('id'))));
            }
            $total = (clone $query)->count();
            $lines = $query->latest()->limit(20)->get()->map(fn ($log) => '• '.$log->created_at->format('d/m/Y H:i').' · '.($log->user?->name ?? 'Sistema').' · '.$log->descripcion);

            return ['answer' => 'Hay '.$total." registros de auditoría dentro de tu acceso. Últimos 20:\n".$lines->join("\n"), 'sources' => [['title' => 'Historial de actividad', 'url' => route('activity.index')]]];
        }
        if (preg_match('/\b(estado del sistema|configuracion del sistema|respaldos|copia de seguridad)\b/', $q)) {
            if (! $user->isAdmin()) {
                return ['answer' => 'La configuración técnica y los respaldos corresponden al administrador.', 'sources' => []];
            }

            return ['answer' => 'Estado actual: '.DB::table('jobs')->count().' trabajos pendientes; '.DB::table('failed_jobs')->count().' fallidos. Groq: '.(filled(config('rag.completion.groq.api_key')) ? 'clave configurada' : 'sin clave configurada').'. Última señal del programador: '.(Cache::get('workspace-scheduler-heartbeat') ?? 'sin registro').'. Último respaldo verificado: '.(! empty(Cache::get('workspace-last-backup')['verified']) ? 'sí' : 'sin registro').'.', 'sources' => [['title' => 'Estado del sistema', 'url' => route('system.index')]]];
        }
        if (preg_match('/\bfaltas\b/', $q)) {
            $areas = WorkspaceAccess::areasFor($user);
            $query = Falta::where('activa', true)->where(fn ($falta) => $falta->whereNull('area')->orWhereIn('area', array_keys($areas)));
            $total = (clone $query)->count();

            return ['answer' => 'Manual: '.$total." faltas aplicables (no son sanciones personales).\n".$query->orderBy('codigo')->limit(20)->get()->map(fn ($falta) => '• '.$falta->codigo.' · '.$falta->titulo.' · '.$falta->gravedadLabel().' · '.($falta->sancion ?? 'Sin sanción definida'))->join("\n"), 'sources' => [['title' => 'Normativa y manual de faltas', 'url' => route('normativa.index')]]];
        }

        return null;
    }
}
