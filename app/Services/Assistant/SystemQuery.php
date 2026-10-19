<?php

namespace App\Services\Assistant;

use App\Models\Employee;
use App\Models\RagDocument;
use App\Models\User;
use App\Services\ProjectMetricsService;
use App\Support\Enums\EmployeeSpecialty;
use App\Support\RagAccessScope;
use App\Support\WorkspaceScope;
use Illuminate\Support\Str;

class SystemQuery
{
    public function interpret(string $question, array $previous = []): array
    {
        $q = Str::lower(Str::ascii($question));
        if (isset($previous['pending_field']) && mb_strlen($question) <= 150) {
            $field = $previous['pending_field'];
            unset($previous['pending_field']);
            if (in_array($field, ['employee_name', 'project_name'])) {
                return [...$previous, $field => trim($question, ' "«»')];
            }
        }
        $entity = match (true) {
            (bool) preg_match('/\b(documentos?|normativa|contratos?|requisitos?|entregables?)\b/', $q) => 'documents',
            (bool) preg_match('/\b(tarea|tareas|actividad|actividades|bloqueos|vencimientos)\b/', $q) => 'tasks',
            (bool) preg_match('/\b(empleados?|colaboradores?|personal|trabajadores?|equipo)\b/', $q) => 'employees',
            (bool) preg_match('/\b(proyectos?|portafolio)\b/', $q) => 'projects',
            default => $previous['entity'] ?? 'documents',
        };
        $plan = $entity === ($previous['entity'] ?? '') ? $previous : [];
        $plan['entity'] = $entity;
        $plan['metric'] = preg_match('/cuant|total|cantidad|numero/', $q) ? 'count' : 'list';
        if ($entity === 'documents') {
            $inventory = preg_match('/(?:cuantos?|cantidad de|numero de|total de)\s+(?:los\s+)?(?:documentos?|archivos?)\b|\b(?:lista|listar|listado|cuales|que)\s+(?:son\s+)?(?:los\s+)?documentos\b|\bdocumentos\s+(?:hay|existen|registrados|subidos)\b/', $q);
            $followup = ($previous['entity'] ?? '') === 'documents' && in_array($previous['metric'] ?? '', ['count', 'list']) && ! preg_match('/\b(documentos?|entregables?|requisitos?|clausulas?|contenido|resume|explica)\b/', $q);
            if (! $inventory && ! $followup) {
                $plan['metric'] = 'content';
            }
        }
        if (preg_match('/\b(todos|todas|general)\b/', $q)) {
            $plan = ['entity' => $entity, 'metric' => $plan['metric']];
        }
        foreach (['inactiv' => 'inactive', 'activ' => 'active', 'pendiente' => 'pending', 'progreso' => 'in_progress', 'revision' => 'review', 'complet' => 'completed', 'bloquead' => 'blocked', 'cancelad' => 'cancelled', 'licencia' => 'on_leave', 'atrasad' => 'overdue'] as $word => $status) {
            if (in_array($word, ['activ', 'inactiv']) && ! preg_match('/\b'.$word.'[oa]s?\b/', $q)) {
                continue;
            }
            if (str_contains($q, $word)) {
                $plan['status'] = $status;
                break;
            }
        }
        if ($entity === 'documents') {
            foreach (['procesados' => 'processed', 'procesado' => 'processed', 'procesando' => 'processing', 'fallidos' => 'failed', 'fallido' => 'failed'] as $word => $status) {
                if (str_contains($q, $word)) {
                    $plan['status'] = $status;
                    break;
                }
            }
        }
        $plan['due'] = match (true) {
            str_contains($q, 'hoy') => 'today', str_contains($q, 'manana') => 'tomorrow', str_contains($q, 'semana') => 'week', default => $plan['due'] ?? ''
        };
        if (preg_match('/\b(mis|mias|mios|tengo)\b/', $q)) {
            $plan['mine'] = true;
        }
        if (preg_match('/\b(urgentes?|criticas?|criticos?)\b/', $q)) {
            $plan['priority'] = 'critical';
        } elseif (str_contains($q, 'prioridad alta')) {
            $plan['priority'] = 'high';
        }
        if (preg_match('/(?:area de |area |en |a )(marketing|software|web|diseno|contenido|audiovisual|estrategia|redes sociales|seo\/sem)/', $q, $m)) {
            $plan['area'] = $m[1];
        }
        if (preg_match('/(?:proyecto)\s+["«](.+?)["»]/u', $question, $m)) {
            $plan['project_name'] = $m[1];
        }
        if (preg_match('/(?:de|para)\s+([A-ZÁÉÍÓÚÑ][\p{L}]+(?:\s+[A-ZÁÉÍÓÚÑ][\p{L}]+)?)/u', $question, $m) && ! in_array(Str::lower($m[1]), ['los', 'las', 'un', 'una', 'marketing', 'software', 'web'])) {
            $plan['employee_name'] = $m[1];
        }
        if (preg_match('/\b(responsable|lider|gerente|avance|presupuesto)\b/', $q) && $entity === 'projects') {
            $plan['metric'] = 'detail';
        }

        return $plan;
    }

    public function run(array $plan, User $user, ?int $projectId = null): array
    {
        $entity = $plan['entity'] ?? 'documents';
        if ($user->isEmployee() && $entity === 'projects') return ['handled' => true, 'answer' => 'Tu rol permite consultar tus actividades, documentos, área y expediente personal. No tienes acceso al módulo de proyectos.', 'sources' => []];
        if (! in_array($entity, ['employees', 'projects', 'tasks', 'documents']) || ($entity === 'documents' && ! in_array($plan['metric'] ?? '', ['count', 'list']))) {
            return ['handled' => false];
        }
        if (! empty($plan['project_name'])) {
            $matches = WorkspaceScope::projects($user)->where(fn ($q) => $q->where('nombre', 'like', '%'.$plan['project_name'].'%')->orWhere('codigo', $plan['project_name']))->limit(3)->get();
            if ($matches->count() !== 1) {
                return [...$this->clarify('proyecto', $matches->pluck('nombre')->all()), 'context' => [...$plan, 'pending_field' => 'project_name']];
            }
            $projectId = $matches->first()->id;
        }
        if ($entity === 'documents') {
            return $this->documents($plan, $user, $projectId);
        }
        $employeeId = null;
        if (! empty($plan['employee_name'])) {
            $parts = preg_split('/\s+/', trim($plan['employee_name']));
            $matches = WorkspaceScope::employees($user)->where(function ($query) use ($parts) {
                foreach ($parts as $part) {
                    $query->where(fn ($q) => $q->where('nombres', 'like', '%'.$part.'%')->orWhere('apellidos', 'like', '%'.$part.'%'));
                }
            })->limit(3)->get();
            if ($matches->count() !== 1) {
                return [...$this->clarify('persona', $matches->map->fullName()->all()), 'context' => [...$plan, 'pending_field' => 'employee_name']];
            }
            $employeeId = $matches->first()->id;
        }
        $status = $plan['status'] ?? '';
        if ($entity === 'employees') {
            $query = WorkspaceScope::employees($user)->with('area');
            if ($employeeId) {
                $query->where('id', $employeeId);
            }
            if (! empty($plan['mine'])) {
                $query->where('id', $user->employee?->id ?? 0);
            }
            if (in_array($status, ['active', 'inactive', 'on_leave'])) {
                $query->where('estado', $status);
            }
            if (! empty($plan['area'])) {
                $query->where(fn ($q) => $q->whereHas('area', fn ($a) => $a->where('nombre', 'like', '%'.$plan['area'].'%'))->orWhereIn('especialidad', $this->specialties($plan['area'])));
            }
            if ($projectId) {
                $query->whereHas('projects', fn ($q) => $q->where('proyectos.id', $projectId)->where('miembros_proyecto.estado', 'active'));
            }
        } elseif ($entity === 'projects') {
            $query = WorkspaceScope::projects($user)->with(['responsibleEmployee', 'manager']);
            if ($projectId) {
                $query->where('id', $projectId);
            }
            if ($employeeId) {
                $query->where('empleado_responsable_id', $employeeId);
            }
            if ($status === 'overdue') {
                $metricsService = app(ProjectMetricsService::class);
                $delayedIds = (clone $query)->get()->filter(function ($p) use ($metricsService) {
                    $metrics = $metricsService->forProject($p);

                    return $metrics['overdue_tasks'] > 0 || $metrics['progress_gap'] > config('decisions.delayed_gap_threshold');
                })->pluck('id')->all();
                $query->whereIn('id', $delayedIds);
            } elseif ($status === 'active') {
                $query->whereNotIn('estado', ['completed', 'cancelled']);
            } elseif (in_array($status, ['pending', 'in_progress', 'review', 'completed', 'blocked', 'cancelled'])) {
                $query->where('estado', $status === 'pending' ? 'planning' : $status);
            }
        } else {
            $query = WorkspaceScope::tasks($user)->whereNull('tarea_padre_id')->with(['assignee', 'project', 'state']);
            if ($projectId) {
                $query->where('proyecto_id', $projectId);
            }
            if ($employeeId) {
                $query->where('asignado_a', $employeeId);
            }
            if (! empty($plan['mine'])) {
                $query->where('asignado_a', $user->employee?->id ?? 0);
            }
            $slugs = ['pending' => 'pendiente', 'in_progress' => 'en-progreso', 'review' => 'en-revision', 'completed' => 'completada', 'blocked' => 'bloqueada', 'cancelled' => 'cancelada'];
            if (isset($slugs[$status])) {
                $query->where(fn ($q) => $q->whereHas('state', fn ($s) => $s->where('slug', $slugs[$status]))->orWhere(fn ($legacy) => $legacy->whereNull('estado_id')->where('estado', $status)));
            }
            if (in_array($status, ['active', 'overdue'])) {
                $this->activeTasks($query);
            }
            if ($status === 'overdue') {
                $query->whereDate('fecha_vencimiento', '<', now()->toDateString());
            }
            $due = $plan['due'] ?? '';
            if (in_array($due, ['today', 'tomorrow'])) {
                $query->whereDate('fecha_vencimiento', $due === 'today' ? now()->toDateString() : now()->addDay()->toDateString());
            }
            if ($due === 'week') {
                $query->whereBetween('fecha_vencimiento', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
            }
            if ($due) {
                $this->activeTasks($query);
            }
            if (! empty($plan['area'])) {
                $query->whereHas('assignee.area', fn ($q) => $q->where('nombre', 'like', '%'.$plan['area'].'%'));
            }
        }
        if (in_array($entity, ['projects', 'tasks']) && in_array($plan['priority'] ?? '', ['low', 'medium', 'high', 'critical'])) {
            $query->where('prioridad', $plan['priority']);
        }
        $total = (clone $query)->count();
        $limit = $entity === 'projects' && ($plan['metric'] ?? '') === 'detail' ? 5 : 20;
        $records = $query->orderBy('id')->limit($limit)->get();
        $labels = ['employees' => 'empleados', 'projects' => 'proyectos', 'tasks' => 'actividades'];
        $singular = ['employees' => 'empleado', 'projects' => 'proyecto', 'tasks' => 'actividad'];
        $label = $total === 1 ? $singular[$entity] : $labels[$entity];
        $verb = $total === 1 ? 'coincide' : 'coinciden';
        $lines = ["Resultado verificado: {$total} {$label} {$verb} con la consulta dentro de tu acceso."];
        $applied = array_filter(['estado' => $plan['status'] ?? '', 'fecha' => $plan['due'] ?? '', 'área' => $plan['area'] ?? '', 'persona' => $plan['employee_name'] ?? '', 'proyecto' => $plan['project_name'] ?? ($projectId ? '#'.$projectId : ''), 'asignación' => ! empty($plan['mine']) ? 'solo mis registros' : '']);
        if ($applied) {
            $labels = ['active' => 'activos', 'inactive' => 'inactivos', 'overdue' => 'atrasados', 'completed' => 'completados', 'pending' => 'pendientes', 'today' => 'hoy', 'week' => 'esta semana', 'month' => 'este mes'];
            $lines[] = 'Filtros: '.collect($applied)->map(fn ($value, $key) => $key.' = '.($labels[$value] ?? $value))->implode('; ').'.';
        }
        $sources = [];
        if (($plan['metric'] ?? 'list') !== 'count') {
            foreach ($records as $record) {
                if ($entity === 'employees') {
                    $title = $record->fullName();
                    $line = $title.' · '.$record->estado->label().' · '.($record->area?->nombre ?? 'Especialidad: '.$record->especialidad->label());
                    $url = $user->can('viewAny', Employee::class) ? route('employees.show', $record) : null;
                } elseif ($entity === 'projects') {
                    $title = $record->nombre;
                    $line = $record->codigo.' · '.$title.' · '.$record->estado->label().' · Líder: '.($record->responsibleEmployee?->fullName() ?? 'Sin asignar').' · Entrega: '.$record->fecha_fin_estimada?->format('d/m/Y');
                    if (($plan['metric'] ?? '') === 'detail') {
                        $metrics = app(ProjectMetricsService::class)->forProject($record);
                        $line .= ' · Avance: '.$metrics['real_progress'].'% · Gerente: '.($record->manager?->fullName() ?? 'Sin asignar');
                    }
                    $url = route('projects.show', $record);
                } else {
                    $title = $record->titulo;
                    $line = $record->codigo.' · '.$title.' · '.($record->state?->nombre ?? $record->estado->label()).' · '.($record->assignee?->fullName() ?? 'Sin asignar').' · '.$record->porcentaje_progreso.'% · Entrega: '.($record->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha');
                    $url = route('tasks.show', $record);
                }
                $lines[] = '• '.$line;
                if ($url) {
                    $sources[] = ['kind' => 'record', 'title' => $title, 'url' => $url];
                }
            }
        }
        if ($total > $limit && ($plan['metric'] ?? '') !== 'count') {
            $lines[] = 'Se muestran los primeros '.$limit.' resultados. Puedes concretar el proyecto, persona, estado o fecha.';
        }
        $lines[] = 'Consultado: '.now()->format('d/m/Y H:i').' ('.config('app.timezone').').';

        return ['handled' => true, 'answer' => implode("\n", $lines), 'sources' => $sources, 'context' => $plan, 'project_id' => $projectId];
    }

    private function clarify(string $type, array $names): array
    {
        return ['handled' => true, 'answer' => $names === [] ? "No encontré coincidencias de {$type} dentro de tu acceso. Indica su nombre o código completo." : 'Hay varias coincidencias. ¿A cuál te refieres? '.implode('; ', $names), 'sources' => []];
    }

    private function documents(array $plan, User $user, ?int $projectId): array
    {
        $ids = RagAccessScope::accessibleProjectIds($user);
        $query = RagDocument::query();
        if ($ids !== null) {
            $query->where(fn ($q) => $q->whereNull('proyecto_id')->orWhereIn('proyecto_id', $ids));
        }
        if ($projectId) {
            $query->where(fn ($q) => $q->whereNull('proyecto_id')->orWhere('proyecto_id', $projectId));
        }
        $status = $plan['status'] ?? '';
        if (in_array($status, ['pending', 'processing', 'processed', 'failed'])) {
            $query->where('estado', $status);
        }
        if (! empty($plan['mine'])) {
            $query->where('subido_por', $user->id);
        }
        $total = (clone $query)->count();
        $documents = $query->orderBy('id')->limit(20)->get();
        $lines = ['Resultado verificado: '.$total.' '.($total === 1 ? 'documento registrado' : 'documentos registrados').' dentro de tu acceso.'];
        if ($status) {
            $lines[] = 'Estado: '.(['pending' => 'pendiente', 'processing' => 'procesando', 'processed' => 'procesado', 'failed' => 'fallido'][$status] ?? $status).'.';
        }
        if ($projectId) {
            $lines[] = 'Alcance: proyecto seleccionado y documentos generales.';
        }
        if (! empty($plan['mine'])) {
            $lines[] = 'Solo documentos subidos por ti.';
        }
        $sources = [];
        foreach ($documents as $document) {
            $sources[] = ['kind' => 'document', 'title' => $document->titulo, 'document_id' => $document->id, 'url' => route('rag-documents.source', $document)];
            if (($plan['metric'] ?? '') === 'list') {
                $lines[] = '• '.$document->titulo.' · '.$document->estado->label();
            }
        }
        if ($total > 20) {
            $lines[] = 'Se muestran enlaces a los primeros 20 documentos; el total incluye todos los registros de la consulta.';
        }
        $lines[] = 'El conteo usa registros de documentos, no sus fragmentos ni referencias de texto.';
        $lines[] = 'Consultado: '.now()->format('d/m/Y H:i').' ('.config('app.timezone').').';

        return ['handled' => true, 'answer' => implode("\n", $lines), 'sources' => $sources, 'context' => $plan, 'project_id' => $projectId];
    }

    private function specialties(string $area): array
    {
        return collect(EmployeeSpecialty::cases())->filter(fn ($s) => $s->tdrArea() === Str::lower(Str::ascii($area)))->map(fn ($s) => $s->value)->all();
    }

    private function activeTasks($query): void
    {
        $query->activeState();
    }
}
