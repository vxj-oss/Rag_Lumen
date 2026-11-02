<?php

namespace App\Http\Controllers\Web\Workspaces;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\TerminosReferencia;
use App\Models\Task;
use App\Support\Enums\EmployeeStatus;
use App\Support\Enums\TaskStatus;
use App\Support\ListPagination;
use App\Support\WorkspaceAccess;
use App\Support\WorkspaceScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $areas = WorkspaceAccess::areasFor($request->user());
        abort_unless(array_key_exists($area, $areas), 403);
        $tdr = TerminosReferencia::porArea($area) ?? abort(404);
        $size = ListPagination::size();
        $memberQuery = WorkspaceScope::employees($request->user())
            ->whereIn('especialidad', WorkspaceAccess::especialidades($area))
            ->where('estado', EmployeeStatus::Active->value);
        $members = (clone $memberQuery)->with('area')->orderBy('nombres')->orderBy('id')
            ->paginate($size, ['*'], 'members_page')->withQueryString();

        // Keep the permission scope in SQL instead of loading all employees and activities.
        $activities = Task::query()->activities()->whereHas('project')
            ->whereIn('asignado_a', (clone $memberQuery)->select('id'))
            ->where('estado', '!=', TaskStatus::Cancelled->value);
        $summarySql = 'COUNT(*) as total, SUM(CASE WHEN estado <> ? THEN 1 ELSE 0 END) as abiertas, '
            .'SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as revision, '
            .'SUM(CASE WHEN estado <> ? AND fecha_vencimiento < ? THEN 1 ELSE 0 END) as atrasadas, '
            .'AVG(porcentaje_progreso) as avance';
        $bindings = [TaskStatus::Completed->value, TaskStatus::Review->value, TaskStatus::Completed->value, now()->toDateTimeString()];
        $totals = (clone $activities)->toBase()->selectRaw($summarySql, $bindings)->first();
        $stateCounts = (clone $activities)->toBase()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $memberStats = (clone $activities)->whereIn('asignado_a', $members->pluck('id'))->toBase()
            ->selectRaw('asignado_a, '.$summarySql, $bindings)->groupBy('asignado_a')->get()->keyBy('asignado_a');
        $porMiembro = $members->getCollection()->mapWithKeys(function (Employee $member) use ($memberStats) {
            $stats = $memberStats->get($member->id);
            return [$member->id => [
                'total' => (int) ($stats->total ?? 0), 'abiertas' => (int) ($stats->abiertas ?? 0),
                'revision' => (int) ($stats->revision ?? 0), 'atrasadas' => (int) ($stats->atrasadas ?? 0),
                'avance' => (int) round((float) ($stats->avance ?? 0)),
            ]];
        });
        $kpis = ['miembros' => $members->total(), 'abiertas' => (int) $totals->abiertas,
            'revision' => (int) $totals->revision, 'atrasadas' => (int) $totals->atrasadas,
            'avance' => (int) round((float) $totals->avance)];
        $relations = ['project:id,codigo,nombre', 'assignee:id,nombres,apellidos'];
        $activityPage = (clone $activities)->with($relations)->withCount([
            'subtareas', 'subtareas as subtareas_completadas_count' => fn ($q) => $q->where('estado', TaskStatus::Completed->value),
        ])->orderBy('fecha_vencimiento')->orderBy('id')->paginate($size, ['*'], 'activities_page')->withQueryString();
        $columns = [
            'asignada' => ['label' => 'Asignadas', 'estados' => [TaskStatus::Pending, TaskStatus::Blocked]],
            'progreso' => ['label' => 'En progreso', 'estados' => [TaskStatus::InProgress]],
            'revision' => ['label' => 'En revisión', 'estados' => [TaskStatus::Review]],
            'aprobada' => ['label' => 'Aprobadas', 'estados' => [TaskStatus::Completed]],
        ];
        $board = collect($columns)->map(fn ($column) => $column + [
            'count' => collect($column['estados'])->sum(fn ($state) => (int) $stateCounts->get($state->value, 0)),
            'items' => $activityPage->getCollection()->filter(fn (Task $task) => in_array($task->estado, $column['estados'], true))->values(),
        ]);
        $proximas = (clone $activities)->where('estado', '!=', TaskStatus::Completed->value)->whereNotNull('fecha_vencimiento')
            ->with($relations)->orderBy('fecha_vencimiento')->orderBy('id')->limit(5)->get();

        // Calculate project summaries in the database, then hydrate only the visible page.
        $projectStats = (clone $activities)->toBase()->selectRaw('proyecto_id, COUNT(*) as actividades, AVG(porcentaje_progreso) as avance')->groupBy('proyecto_id');
        $proyectos = Project::query()->joinSub($projectStats, 'area_progress', fn ($join) => $join->on('proyectos.id', '=', 'area_progress.proyecto_id'))
            ->select('proyectos.*', 'area_progress.actividades', 'area_progress.avance')->with('client:id,nombre')
            ->orderBy('proyectos.nombre')->orderBy('proyectos.id')->paginate($size, ['proyectos.*'], 'projects_page')->withQueryString()
            ->through(fn (Project $project) => ['proyecto' => $project, 'actividades' => (int) $project->actividades, 'avance' => (int) round((float) $project->avance)]);

        return view('workspaces.show', [
            'area' => $area, 'areas' => $areas, 'tema' => WorkspaceAccess::tema($area), 'tdr' => $tdr,
            'faltas' => $tdr->faltasAplicables(), 'members' => $members, 'board' => $board, 'kpis' => $kpis,
            'porMiembro' => $porMiembro, 'proyectos' => $proyectos, 'activityPage' => $activityPage,
            'proximas' => $proximas, 'totalActividades' => (int) $totals->total,
        ]);
    }
}
