<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\RoleName;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeUser(RoleName $role, bool $withEmployee = true): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        if ($withEmployee) {
            Employee::factory()->create([
                'usuario_id' => $user->id,
                'correo' => $user->email,
            ]);
        }

        return $user;
    }

    private function projectPayload(): array
    {
        return [
            'codigo' => 'TST-001',
            'nombre' => 'Proyecto de prueba',
            'tipo' => 'digital_marketing',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin_estimada' => now()->addMonth()->toDateString(),
            'estado' => 'planning',
            'prioridad' => 'medium',
        ];
    }

    public function test_admin_can_create_projects(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);

        $response = $this->actingAs($admin)->post(route('projects.store'), $this->projectPayload());

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('proyectos', ['codigo' => 'TST-001']);
    }

    public function test_manager_can_create_projects(): void
    {
        $manager = $this->makeUser(RoleName::Manager);

        $response = $this->actingAs($manager)->post(route('projects.store'), $this->projectPayload());

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('proyectos', ['codigo' => 'TST-001']);
    }

    public function test_lead_cannot_create_projects(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);

        $response = $this->actingAs($lead)->post(route('projects.store'), $this->projectPayload());

        $response->assertForbidden();
    }

    public function test_employee_cannot_create_projects(): void
    {
        $employee = $this->makeUser(RoleName::Employee);

        $response = $this->actingAs($employee)->post(route('projects.store'), $this->projectPayload());

        $response->assertForbidden();
    }

    public function test_employee_sees_only_member_projects(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $stranger = Employee::factory()->create();

        $visible = Project::factory()->create(['empleado_responsable_id' => $stranger->id]);
        $hidden = Project::factory()->create(['empleado_responsable_id' => $stranger->id]);
        $visible->members()->attach($employee->employee->id, [
            'rol_en_proyecto' => 'Miembro',
            'asignado_en' => now()->toDateString(),
            'estado' => 'active',
        ]);

        $response = $this->actingAs($employee)->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee($visible->nombre);
        $response->assertDontSee($hidden->nombre);
    }

    public function test_employee_cannot_access_activity(): void
    {
        $employee = $this->makeUser(RoleName::Employee);

        $this->actingAs($employee)->get(route('activity.index'))->assertForbidden();
    }

    public function test_lead_can_access_scoped_activity(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);

        $this->actingAs($lead)->get(route('activity.index'))->assertOk();
    }

    public function test_employee_cannot_list_employees_but_lead_can(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $lead = $this->makeUser(RoleName::ProjectLead);

        $this->actingAs($employee)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($lead)->get(route('employees.index'))->assertOk();
    }

    public function test_manager_can_update_any_project_lead_only_own(): void
    {
        $manager = $this->makeUser(RoleName::Manager);
        $lead = $this->makeUser(RoleName::ProjectLead);
        $otherLead = $this->makeUser(RoleName::ProjectLead);

        $area = \App\Models\Area::create(['nombre' => 'Estrategia']);
        $manager->employee->update(['area_id' => $area->id]);

        $project = Project::factory()->create(['empleado_responsable_id' => $lead->employee->id]);
        $project->areas()->sync([$area->id]);

        $payload = array_merge($this->projectPayload(), ['codigo' => $project->codigo]);

        $this->actingAs($manager)->put(route('projects.update', $project), $payload)->assertRedirect();
        $this->actingAs($lead)->put(route('projects.update', $project), $payload)->assertRedirect();
        $this->actingAs($otherLead)->put(route('projects.update', $project), $payload)->assertForbidden();
    }

    public function test_employee_can_view_member_project_but_not_others(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $stranger = Employee::factory()->create();

        $visible = Project::factory()->create(['empleado_responsable_id' => $stranger->id]);
        $hidden = Project::factory()->create(['empleado_responsable_id' => $stranger->id]);
        $visible->members()->attach($employee->employee->id, [
            'rol_en_proyecto' => 'Miembro',
            'asignado_en' => now()->toDateString(),
            'estado' => 'active',
        ]);

        $this->actingAs($employee)->get(route('projects.show', $visible))->assertOk();
        $this->actingAs($employee)->get(route('projects.show', $hidden))->assertForbidden();
    }

    public function test_employee_can_view_own_task_but_not_unrelated(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $stranger = Employee::factory()->create();
        $otherProject = Project::factory()->create(['empleado_responsable_id' => $stranger->id]);

        $mine = Task::factory()->create(['asignado_a' => $employee->employee->id]);
        $other = Task::factory()->create([
            'proyecto_id' => $otherProject->id,
            'asignado_a' => $stranger->id,
        ]);

        $this->actingAs($employee)->get(route('tasks.show', $mine))->assertOk();
        $this->actingAs($employee)->get(route('tasks.show', $other))->assertForbidden();
    }
}
