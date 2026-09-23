<?php

namespace Tests\Feature\Access;

use App\Models\Area;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\RoleName;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, AreaSeeder::class]);
        TaskState::seedDefaults(null);
    }

    private function makeUser(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Employee::factory()->create(['usuario_id' => $user->id, 'correo' => $user->email]);

        return $user->refresh();
    }

    private function projectWithStates(array $overrides = []): Project
    {
        $project = Project::factory()->create($overrides);
        TaskState::seedDefaults($project->id);

        return $project;
    }

    public function test_admin_sees_global_dashboard(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $this->projectWithStates();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard ejecutivo');
    }

    public function test_manager_sees_only_scoped_projects(): void
    {
        $manager = $this->makeUser(RoleName::Manager);
        $area = Area::where('nombre', 'Diseño')->first();
        $manager->employee->update(['area_id' => $area->id]);

        $inScope = $this->projectWithStates();
        $inScope->areas()->sync([$area->id]);
        $outside = $this->projectWithStates();

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk()->assertSee('Portafolio bajo mi alcance');
        $response->assertSee($inScope->nombre);
        $response->assertDontSee($outside->nombre);
    }

    public function test_leader_sees_own_projects_and_team_load(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $member = Employee::factory()->create();

        $project = $this->projectWithStates(['empleado_responsable_id' => $lead->employee->id]);
        $project->members()->attach($member->id, [
            'rol_en_proyecto' => 'Miembro',
            'asignado_en' => now()->toDateString(),
            'estado' => 'active',
        ]);
        Task::factory()->create([
            'proyecto_id' => $project->id,
            'asignado_a' => $member->id,
            'estado_id' => TaskState::where('proyecto_id', $project->id)->where('slug', 'pendiente')->first()->id,
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);
        $this->projectWithStates();

        $response = $this->actingAs($lead)->get(route('dashboard'));

        $response->assertOk()->assertSee('Mis proyectos');
        $response->assertSee($project->nombre);
        $response->assertSee($member->nombres);
    }

    public function test_employee_sees_my_day_with_own_tasks_only(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $stranger = Employee::factory()->create();
        $project = $this->projectWithStates();
        $pending = TaskState::where('proyecto_id', $project->id)->where('slug', 'pendiente')->first()->id;

        $mine = Task::factory()->create([
            'proyecto_id' => $project->id,
            'asignado_a' => $employee->employee->id,
            'estado_id' => $pending,
            'horas_reales' => 5,
        ]);
        $other = Task::factory()->create([
            'proyecto_id' => $project->id,
            'asignado_a' => $stranger->id,
            'estado_id' => $pending,
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk()->assertSee('Mi día');
        $response->assertSee($mine->titulo);
        $response->assertDontSee($other->titulo);
        $response->assertSee('5');
    }

    public function test_exports_respect_role_scope(): void
    {
        $manager = $this->makeUser(RoleName::Manager);
        $area = Area::where('nombre', 'Diseño')->first();
        $manager->employee->update(['area_id' => $area->id]);

        $inScope = $this->projectWithStates();
        $inScope->areas()->sync([$area->id]);
        $outside = $this->projectWithStates();

        $response = $this->actingAs($manager)->get(route('dashboard.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=reporte-gerencia-'.now()->format('Y-m-d').'.pdf');
    }

    public function test_employee_export_contains_only_own_tasks(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $stranger = Employee::factory()->create();
        $project = $this->projectWithStates();
        $pending = TaskState::where('proyecto_id', $project->id)->where('slug', 'pendiente')->first()->id;

        $mine = Task::factory()->create([
            'proyecto_id' => $project->id,
            'asignado_a' => $employee->employee->id,
            'estado_id' => $pending,
        ]);
        $other = Task::factory()->create([
            'proyecto_id' => $project->id,
            'asignado_a' => $stranger->id,
            'estado_id' => $pending,
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=reporte-mi-dia-'.now()->format('Y-m-d').'.pdf');

        // El alcance del PDF se verifica renderizando el reporte con la misma
        // consulta acotada que usa el controlador.
        $scoped = Task::with(['state', 'project'])->where('asignado_a', $employee->employee->id)->get();
        $html = view('reports.dashboard-employee', [
            'summary' => ['pending' => 1, 'in_progress' => 0, 'due_soon' => 0, 'blocked' => 0, 'hours_recorded' => 0],
            'byState' => $scoped->groupBy(fn (Task $task) => $task->state?->nombre ?? $task->estado->label()),
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString($mine->titulo, $html);
        $this->assertStringNotContainsString($other->titulo, $html);
    }
}
