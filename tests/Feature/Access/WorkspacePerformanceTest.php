<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TerminosReferencia;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkspacePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_lists_load_only_page_records_and_keep_complete_totals(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(); $admin->assignRole('administrator');
        TerminosReferencia::firstOrCreate(['area' => 'software'], ['nombre' => 'TDR Software', 'contenido' => 'Contenido', 'version' => 1]);
        $employees = Employee::factory()->count(25)->create(['especialidad' => 'backend']);
        $project = Project::factory()->create(['estado' => 'in_progress']);
        Task::withoutEvents(fn () => Task::factory()->count(120)->create([
            'proyecto_id' => $project->id, 'asignado_a' => $employees->first()->id,
            'estado' => 'pending', 'porcentaje_progreso' => 40,
            'fecha_vencimiento' => now()->addDays(4),
        ]));
        $outside = Employee::factory()->create(['especialidad' => 'marketing']);
        Task::withoutEvents(fn () => Task::factory()->create(['proyecto_id' => $project->id, 'asignado_a' => $outside->id, 'titulo' => 'Actividad ajena al área']));
        $tasks = 0; $members = 0;
        Task::retrieved(function () use (&$tasks) { $tasks++; });
        Employee::retrieved(function () use (&$members) { $members++; });
        $durations = [];
        for ($i = 0; $i < 3; $i++) {
            $tasks = $members = 0; DB::flushQueryLog(); DB::enableQueryLog(); $start = hrtime(true);
            $response = $this->actingAs($admin)->get(route('workspaces.show', 'software'));
            $durations[] = (hrtime(true) - $start) / 1e6;
            $queries = count(DB::getQueryLog()); DB::disableQueryLog();
            $response->assertOk()->assertDontSee('Actividad ajena al área')
                ->assertViewHas('totalActividades', 120)
                ->assertViewHas('board', fn ($board) => $board['asignada']['count'] === 120 && $board['asignada']['items']->count() === 5)
                ->assertViewHas('proyectos', fn ($page) => $page->total() === 1 && $page->first()['actividades'] === 120 && $page->first()['avance'] === 40)
                ->assertViewHas('kpis', fn ($kpis) => $kpis['miembros'] === 25 && $kpis['abiertas'] === 120 && $kpis['avance'] === 40)
                ->assertViewHas('members', fn ($page) => $page->count() === 5 && $page->total() === 25);
        }
        sort($durations);
        fwrite(STDOUT, sprintf("\nWorkspace profile: tasks=%d employees=%d queries=%d median=%.1fms\n", $tasks, $members, $queries, $durations[1]));
        $this->assertLessThanOrEqual(10, $tasks, 'Only current-page activities and upcoming deliveries should be hydrated.');
        $this->assertLessThanOrEqual(15, $members, 'Only page employees and related assignees should be hydrated.');
    }

    public function test_metrics_reuse_reads_and_refresh_after_task_updates(): void
    {
        $employees = Employee::factory()->count(3)->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'proyecto_id' => $project->id, 'asignado_a' => $employees->first()->id,
            'estado' => 'pending', 'porcentaje_progreso' => 20, 'estado_id' => null,
        ]);
        $metrics = app(\App\Services\ProjectMetricsService::class);
        DB::flushQueryLog(); DB::enableQueryLog();
        $counts = $metrics->activeTaskCounts($employees->pluck('id'));
        $this->assertCount(1, DB::getQueryLog(), 'Employee workloads should use one grouped query.');
        $this->assertSame(1, $counts[$employees->first()->id]);
        $this->assertSame(0, $counts[$employees->last()->id]);
        $metrics->activeTaskCounts($employees->pluck('id'));
        $this->assertCount(1, DB::getQueryLog(), 'Repeated workload reads in one request reuse their result.');
        DB::disableQueryLog();
        $this->assertSame(20.0, $metrics->forProject($project)['real_progress']);
        $this->assertCount(1, $metrics->leafTasks($project));
        $task->update(['estado' => 'completed', 'porcentaje_progreso' => 100]);
        $this->assertSame(100.0, $metrics->forProject($project)['real_progress']);
        $this->assertSame(0, $metrics->activeTaskCounts($employees->pluck('id'))[$employees->first()->id]);
        $task->delete();
        $this->assertSame(0, $metrics->forProject($project)['total_tasks']);
    }
}
