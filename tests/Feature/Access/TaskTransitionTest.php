<?php

namespace Tests\Feature\Access;

use App\Models\Area;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\RoleName;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        TaskState::seedDefaults(null);
    }

    private function makeUser(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Employee::factory()->create(['user_id' => $user->id, 'email' => $user->email]);

        return $user;
    }

    private function projectWithStates(?int $responsibleId = null): Project
    {
        $project = Project::factory()->create(['responsible_employee_id' => $responsibleId]);
        TaskState::seedDefaults($project->id);

        return $project;
    }

    private function stateId(Project $project, string $slug): int
    {
        return TaskState::where('project_id', $project->id)->where('slug', $slug)->firstOrFail()->id;
    }

    public function test_employee_can_move_own_task_to_review_but_not_completed(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $project = $this->projectWithStates();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $employee->employee->id,
            'status_id' => $this->stateId($project, 'en-progreso'),
        ]);

        $this->actingAs($employee)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $this->stateId($project, 'en-revision')])
            ->assertOk();

        $this->assertSame('en-revision', $task->refresh()->state->slug);

        $this->actingAs($employee)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $this->stateId($project, 'completada')])
            ->assertForbidden();

        $this->assertSame('en-revision', $task->refresh()->state->slug);
    }

    public function test_lead_can_complete_task_of_own_project(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = $this->projectWithStates($lead->employee->id);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status_id' => $this->stateId($project, 'en-revision'),
        ]);

        $this->actingAs($lead)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $this->stateId($project, 'completada')])
            ->assertOk();

        $task->refresh();
        $this->assertSame('completada', $task->state->slug);
        $this->assertSame('completed', $task->status->value);
        $this->assertSame(100, $task->progress_percentage);
    }

    public function test_lead_cannot_complete_task_of_foreign_project(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $stranger = Employee::factory()->create();
        $project = $this->projectWithStates($stranger->id);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $stranger->id,
            'status_id' => $this->stateId($project, 'en-revision'),
        ]);

        $this->actingAs($lead)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $this->stateId($project, 'completada')])
            ->assertForbidden();

        $this->assertSame('en-revision', $task->refresh()->state->slug);
    }

    public function test_manager_can_complete_task_in_scope(): void
    {
        $manager = $this->makeUser(RoleName::Manager);
        $area = Area::create(['name' => 'Estrategia']);
        $manager->employee->update(['area_id' => $area->id]);
        $project = $this->projectWithStates();
        $project->areas()->sync([$area->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status_id' => $this->stateId($project, 'en-revision'),
        ]);

        $this->actingAs($manager)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $this->stateId($project, 'completada')])
            ->assertOk();

        $this->assertSame('completada', $task->refresh()->state->slug);
    }

    public function test_form_update_rejects_employee_completion(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $project = $this->projectWithStates();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $employee->employee->id,
            'status_id' => $this->stateId($project, 'en-progreso'),
        ]);

        $response = $this->actingAs($employee)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'priority' => 'high',
            'status_id' => $this->stateId($project, 'completada'),
        ]);

        $response->assertSessionHasErrors('status_id');
        $this->assertSame('en-progreso', $task->refresh()->state->slug);
    }

    public function test_move_records_status_history(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = $this->projectWithStates($lead->employee->id);
        $from = $this->stateId($project, 'pendiente');
        $to = $this->stateId($project, 'en-progreso');
        $task = Task::factory()->create(['project_id' => $project->id, 'status_id' => $from]);

        $this->actingAs($lead)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $to])
            ->assertOk();

        $this->assertDatabaseHas('task_status_history', [
            'task_id' => $task->id,
            'from_status_id' => $from,
            'to_status_id' => $to,
            'user_id' => $lead->id,
        ]);
    }

    public function test_blocking_move_requires_reason(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $project = $this->projectWithStates();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $employee->employee->id,
            'status_id' => $this->stateId($project, 'en-progreso'),
            'blocked_reason' => null,
        ]);

        $this->actingAs($employee)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $this->stateId($project, 'bloqueada')])
            ->assertStatus(422);

        $this->actingAs($employee)
            ->patchJson(route('tasks.status.update', $task), [
                'status_id' => $this->stateId($project, 'bloqueada'),
                'blocked_reason' => 'Falta el acceso al CMS.',
            ])
            ->assertOk();

        $task->refresh();
        $this->assertSame('bloqueada', $task->state->slug);
        $this->assertSame('Falta el acceso al CMS.', $task->blocked_reason);
    }
}
