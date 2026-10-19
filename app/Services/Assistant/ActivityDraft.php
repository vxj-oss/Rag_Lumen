<?php

namespace App\Services\Assistant;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\TaskStatus;
use App\Support\WorkspaceScope;
use Illuminate\Support\Str;

class ActivityDraft
{
    public function prepare(string $question, User $user, ?Project $context): array
    {
        if (! $user->can('create', Task::class)) {
            return ['answer' => 'Tu rol no permite crear actividades. Puede hacerlo un administrador o el líder del proyecto.', 'sources' => []];
        }
        $projects = WorkspaceScope::projects($user)->when(!$user->isAdmin() && !$user->isManager(), fn ($q) => $q->where('empleado_responsable_id', $user->employee?->id))
            ->with(['members' => fn ($q) => $q->where('miembros_proyecto.estado', 'active')])->orderBy('nombre')->get();
        $choices = $projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->nombre, 'state_id' => TaskState::resolveForStatus($p->id, TaskStatus::Pending)?->id,
            'members' => $p->members->map(fn ($e) => ['id' => $e->id, 'name' => $e->fullName()])->values()])->values()->all();
        if (! $choices) {
            return ['answer' => 'No hay proyectos disponibles en los que tengas permiso para crear actividades.', 'sources' => []];
        }
        $draft = ['title' => '', 'description' => '', 'project_id' => $context?->id ?? '', 'assignee_id' => '', 'priority' => 'medium', 'start' => '', 'due' => '', 'tasks' => [['title' => '', 'description' => '']]];
        if (preg_match('/(?:actividad)\s+["«](.+?)["»]/iu', $question, $m)) {
            $draft['title'] = Str::limit($m[1], 200, '');
        } elseif (preg_match('/\bactividad\s+(?:llamada\s+|titulada\s+)?(.+?)(?:\s+(?:en el proyecto|para el proyecto|asignada a|para manana|para hoy|con prioridad)|[.;]|$)/iu', $question, $m)) {
            $draft['title'] = Str::limit(trim($m[1]), 200, '');
        }
        if (preg_match('/\btareas:\s*(.+)$/iu', $question, $m)) {
            $titles = array_values(array_filter(array_map('trim', explode(';', $m[1]))));
            if ($titles) {
                $draft['tasks'] = array_map(fn ($title) => ['title' => Str::limit($title, 200, ''), 'description' => ''], array_slice($titles, 0, 30));
            }
        }
        foreach ($projects as $p) {
            if (str_contains(Str::lower($question), Str::lower($p->nombre)) || str_contains(Str::upper($question), Str::upper($p->codigo))) {
                $draft['project_id'] = $p->id;
            }
        }
        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $question, $m)) {
            $draft['due'] = $m[1];
        }
        $q = Str::lower(Str::ascii($question));
        if (str_contains($q, 'manana')) {
            $draft['due'] = now()->addDay()->toDateString();
        } elseif (str_contains($q, 'hoy')) {
            $draft['due'] = now()->toDateString();
        }
        foreach (['alta' => 'high', 'baja' => 'low', 'critica' => 'critical', 'urgente' => 'critical'] as $word => $priority) {
            if (str_contains($q, $word)) {
                $draft['priority'] = $priority;
            }
        }
        $selected = $projects->firstWhere('id', $draft['project_id']);
        if ($selected) {
            $matches = $selected->members->filter(fn ($e) => str_contains($q, Str::lower(Str::ascii($e->fullName()))));
            if ($matches->count() === 1) {
                $draft['assignee_id'] = $matches->first()->id;
            }
        }

        return ['answer' => 'Preparé un borrador. Revisa proyecto, responsable, fechas y al menos una tarea. Al pulsar Crear actividad se guardará con las mismas validaciones y permisos del formulario habitual. Nada se guarda hasta ese paso.',
            'sources' => [], 'action' => ['type' => 'create_activity', 'url' => route('tasks.store'), 'projects' => $choices, 'draft' => $draft]];
    }
}
