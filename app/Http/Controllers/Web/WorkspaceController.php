<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\TerminosReferencia;
use App\Models\Task;
use App\Support\Enums\EmployeeStatus;
use App\Support\Enums\TaskStatus;
use App\Support\WorkspaceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espacio independiente por área (software, web, marketing): equipo,
 * actividades, TDR/faltas y proyectos de los colaboradores de esa área.
 */
class WorkspaceController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $areas = WorkspaceAccess::areasFor($request->user());

        abort_if($areas === [], 403, 'Tu perfil no tiene un espacio de área asignado.');

        return redirect()->route('workspaces.show', array_key_first($areas));
    }

    public function show(Request $request, string $area): View
    {
        abort_unless(array_key_exists($area, WorkspaceAccess::areasFor($request->user())), 403);

        $tdr = TerminosReferencia::porArea($area) ?? abort(404);

        $members = Employee::with('area')
            ->whereIn('especialidad', WorkspaceAccess::especialidades($area))
            ->where('estado', EmployeeStatus::Active->value)
            ->orderBy('nombres')
            ->get();

        $memberIds = $members->pluck('id');

        $activities = Task::query()
            ->whereNull('tarea_padre_id')
            ->whereHas('project')
            ->whereIn('asignado_a', $memberIds)
            ->where('estado', '!=', TaskStatus::Cancelled->value)
            ->withCount([
                'subtareas',
                'subtareas as subtareas_completadas_count' => fn ($q) => $q->where('estado', TaskStatus::Completed->value),
            ])
            ->with(['project:id,codigo,nombre', 'assignee:id,nombres,apellidos'])
            ->orderBy('fecha_vencimiento')
            ->get();

        $columnas = [
            'asignada' => ['label' => 'Asignadas', 'estados' => [TaskStatus::Pending, TaskStatus::Blocked]],
            'progreso' => ['label' => 'En progreso', 'estados' => [TaskStatus::InProgress]],
            'revision' => ['label' => 'En revisión', 'estados' => [TaskStatus::Review]],
            'aprobada' => ['label' => 'Aprobadas', 'estados' => [TaskStatus::Completed]],
        ];

        $board = collect($columnas)->map(fn (array $col) => $col + [
            'items' => $activities->filter(fn (Task $t) => in_array($t->estado, $col['estados'], true))->values(),
        ]);

        $abiertas = $activities->filter(fn (Task $t) => $t->estado !== TaskStatus::Completed);

        $kpis = [
            'miembros' => $members->count(),
            'abiertas' => $abiertas->count(),
            'revision' => $activities->where('estado', TaskStatus::Review)->count(),
            'atrasadas' => $abiertas->filter(fn (Task $t) => $t->fecha_vencimiento?->isPast())->count(),
            'avance' => $activities->isEmpty() ? 0 : (int) round($activities->avg('porcentaje_progreso')),
        ];

        $porMiembro = $members->mapWithKeys(function (Employee $m) use ($activities) {
            $mias = $activities->where('asignado_a', $m->id);

            return [$m->id => [
                'total' => $mias->count(),
                'abiertas' => $mias->filter(fn (Task $t) => $t->estado !== TaskStatus::Completed)->count(),
                'revision' => $mias->where('estado', TaskStatus::Review)->count(),
                'atrasadas' => $mias->filter(fn (Task $t) => $t->estado !== TaskStatus::Completed && $t->fecha_vencimiento?->isPast())->count(),
                'avance' => $mias->isEmpty() ? 0 : (int) round($mias->avg('porcentaje_progreso')),
            ]];
        });

        $proyectos = Project::query()
            ->with('client:id,nombre')
            ->whereIn('id', $activities->pluck('proyecto_id')->unique())
            ->orderBy('nombre')
            ->get()
            ->map(function (Project $p) use ($activities) {
                $delArea = $activities->where('proyecto_id', $p->id);

                return [
                    'proyecto' => $p,
                    'actividades' => $delArea->count(),
                    'avance' => (int) round($delArea->avg('porcentaje_progreso')),
                ];
            });

        $proximas = $abiertas->filter(fn (Task $t) => $t->fecha_vencimiento !== null)->sortBy('fecha_vencimiento')->take(5)->values();

        return view('workspaces.html.show', [
            'area' => $area,
            'areas' => WorkspaceAccess::areasFor($request->user()),
            'tema' => WorkspaceAccess::tema($area),
            'tdr' => $tdr,
            'faltas' => $tdr->faltasAplicables(),
            'members' => $members,
            'board' => $board,
            'kpis' => $kpis,
            'porMiembro' => $porMiembro,
            'proyectos' => $proyectos,
            'proximas' => $proximas,
            'totalActividades' => $activities->count(),
        ]);
    }
}
