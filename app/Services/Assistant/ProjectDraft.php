<?php

namespace App\Services\Assistant;

use App\Models\Area;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\ProjectType;
use App\Support\WorkspaceScope;
use Illuminate\Support\Str;

class ProjectDraft
{
    public function prepare(string $question, User $user): array
    {
        if (! $user->can('create', Project::class)) {
            return ['answer' => 'Solo el administrador o gerente puede crear proyectos.', 'sources' => []];
        }
        $title = '';
        if (preg_match('/\bproyecto\s+(?:llamado\s+|titulado\s+)?["«]?(.+?)["»]?(?:\s+(?:con fecha|con prioridad|para el cliente)|$)/iu', $question, $m)) {
            $title = trim($m[1], ' "«».');
        }

        return ['answer' => 'Preparé el proyecto como borrador. Revisa los datos, las áreas y las fechas antes de pulsar Crear proyecto.', 'sources' => [],
            'action' => ['type' => 'create_project', 'url' => route('projects.store'),
                'clients' => Client::orderBy('nombre')->get(['id', 'nombre']),
                'employees' => WorkspaceScope::employees($user)->orderBy('nombres')->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->fullName()])->values(),
                'areas' => Area::where('activa', true)->orderBy('nombre')->get(['id', 'nombre']),
                'types' => collect(ProjectType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]),
                'draft' => ['name' => Str::limit($title, 150, ''), 'code' => 'PR-'.now()->format('ymd').'-'.Str::upper(Str::random(4)), 'type' => 'other', 'client_id' => '', 'responsible_id' => '', 'manager_id' => $user->isManager() ? $user->employee?->id : '', 'area_ids' => [], 'start' => now()->toDateString(), 'end' => '', 'priority' => 'medium', 'budget' => '', 'description' => '']]];
    }
}
