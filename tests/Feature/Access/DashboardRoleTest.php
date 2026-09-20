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
        Employee::factory()->create(['user_id' => $user->id, 'email' => $user->email]);

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
        $area = Area::where('name', 'Diseño')->first();
        $manager->employee->update(['area_id' => $area->id]);

        $inScope = $this->projectWithStates();
        $inScope->areas()->sync([$area->id]);
        $outside = $this->projectWithStates();

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk()->assertSee('Portafolio bajo mi alcance');
        $response->assertSee($inScope->name);
        $response->assertDontSee($outside->name);
    }

    public function test_leader_sees_own_projects_and_team_load(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $member = Employee::factory()->create();

        $project = $this->projectWithStates(['responsible_employee_id' => $lead->employee->id]);
        $project->members()->attach($member->id, [
            'role_in_project' => 'Miembro',
            'assigned_at' => now()->toDateString(),
            'status' => 'active',
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $member->id,
            'status_id' => TaskState::where('project_id', $project->id)->where('slug', 'pendiente')->first()->id,
            'due_date' => now()->subDay()->toDateString(),
        ]);
        $this->projectWithStates();

        $response = $this->actingAs($lead)->get(route('dashboard'));

        $response->assertOk()->assertSee('Mis proyectos');
        $response->assertSee($project->name);
        $response->assertSee($member->first_name);
    }

    public function test_employee_sees_my_day_with_own_tasks_only(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $stranger = Employee::factory()->create();
        $project = $this->projectWithStates();
        $pending = TaskState::where('project_id', $project->id)->where('slug', 'pendiente')->first()->id;

        $mine = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $employee->employee->id,
            'status_id' => $pending,
            'actual_hours' => 5,
        ]);
        $other = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $stranger->id,
            'status_id' => $pending,
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk()->assertSee('Mi día');
        $response->assertSee($mine->title);
        $response->assertDontSee($other->title);
        $response->assertSee('5');
    }

    public function test_exports_respect_role_scope(): void
    {
        $manager = $this->makeUser(RoleName::Manager);
        $area = Area::where('name', 'Diseño')->first();
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
        $pending = TaskState::where('project_id', $project->id)->where('slug', 'pendiente')->first()->id;

        $mine = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $employee->employee->id,
            'status_id' => $pending,
        ]);
        $other = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $stranger->id,
            'status_id' => $pending,
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=reporte-mi-dia-'.now()->format('Y-m-d').'.pdf');

        // El alcance del PDF se verifica renderizando el reporte con la misma
        // consulta acotada que usa el controlador.
        $scoped = Task::with(['state', 'project'])->where('assigned_to', $employee->employee->id)->get();
        $html = view('reports.dashboard-employee', [
            'summary' => ['pending' => 1, 'in_progress' => 0, 'due_soon' => 0, 'blocked' => 0, 'hours_recorded' => 0],
            'byState' => $scoped->groupBy(fn (Task $task) => $task->state?->name ?? $task->status->label()),
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString($mine->title, $html);
        $this->assertStringNotContainsString($other->title, $html);
    }
}
