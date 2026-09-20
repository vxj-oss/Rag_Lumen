<?php

namespace Tests\Feature\Access;

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

class TaskBoardTest extends TestCase
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

    public function test_board_returns_columns_kpis_and_cards(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $project = Project::factory()->create();
        TaskState::seedDefaults($project->id);
        Task::factory()->create([
            'project_id' => $project->id,
            'status_id' => TaskState::where('project_id', $project->id)->where('slug', 'pendiente')->first()->id,
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('tasks.board'));

        $response->assertOk()
            ->assertJsonPath('kpis.open', 1)
            ->assertJsonPath('kpis.overdue', 1)
            ->assertJsonCount(6, 'columns')
            ->assertJsonCount(1, 'cards')
            ->assertJsonFragment(['code' => $project->code.'-T1']);
    }

    public function test_task_view_keeps_sidebar_navigation_native_and_cleans_up_async_work(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);

        $response = $this->actingAs($admin)->get(route('tasks.index'));

        $response->assertOk()
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertDontSee('<a @click="mobileOpen = false"', false)
            ->assertSee('new AbortController()', false)
            ->assertSee("window.addEventListener('pagehide'", false)
            ->assertSee('destroy() {', false);
    }

    public function test_task_detail_stops_live_polling_before_navigation(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $task = Task::factory()->create();

        $response = $this->actingAs($admin)->get(route('tasks.show', $task));

        $response->assertOk()
            ->assertSee('function stopPolling()', false)
            ->assertSee("document.addEventListener('click'", false)
            ->assertSee("window.addEventListener('pagehide', stopPolling", false)
            ->assertSee('requestController?.abort()', false)
            ->assertSee('if (controller.signal.aborted || navigating) return;', false);
    }

    public function test_board_filters_by_project_and_mine(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $project = Project::factory()->create();
        TaskState::seedDefaults($project->id);
        $other = Project::factory()->create();
        TaskState::seedDefaults($other->id);

        foreach ([$project, $other] as $p) {
            $p->members()->attach($employee->employee->id, [
                'role_in_project' => 'Miembro',
                'assigned_at' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $employee->employee->id,
            'status_id' => TaskState::where('project_id', $project->id)->where('slug', 'pendiente')->first()->id,
        ]);
        Task::factory()->create([
            'project_id' => $other->id,
            'assigned_to' => Employee::factory()->create()->id,
            'status_id' => TaskState::where('project_id', $other->id)->where('slug', 'pendiente')->first()->id,
        ]);

        $this->actingAs($employee)->getJson(route('tasks.board', ['project_id' => $project->id]))
            ->assertOk()->assertJsonCount(1, 'cards');

        $this->actingAs($employee)->getJson(route('tasks.board', ['mine' => 1]))
            ->assertOk()->assertJsonCount(1, 'cards');
    }

    public function test_task_code_is_generated_on_create(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $project = Project::factory()->create(['code' => 'PRJ-9']);
        TaskState::seedDefaults($project->id);
        $initial = TaskState::where('project_id', $project->id)->where('is_initial', true)->firstOrFail();

        $this->actingAs($admin)->post(route('tasks.store'), [
            'project_id' => $project->id,
            'title' => 'Tarea con código',
            'status_id' => $initial->id,
            'priority' => 'medium',
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', ['code' => 'PRJ-9-T1', 'project_id' => $project->id]);
        $this->assertSame(1, $project->refresh()->task_counter);
    }

    public function test_store_rejects_assignee_outside_project(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $project = Project::factory()->create();
        TaskState::seedDefaults($project->id);
        $outsider = Employee::factory()->create();
        $initial = TaskState::where('project_id', $project->id)->where('is_initial', true)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('tasks.store'), [
            'project_id' => $project->id,
            'title' => 'Tarea inválida',
            'assigned_to' => $outsider->id,
            'status_id' => $initial->id,
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('assigned_to');
    }

    public function test_lead_can_manage_project_states(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = Project::factory()->create(['responsible_employee_id' => $lead->employee->id]);
        TaskState::seedDefaults($project->id);

        $this->actingAs($lead)->get(route('projects.statuses.index', $project))->assertOk();

        $this->actingAs($lead)->post(route('projects.statuses.store', $project), [
            'name' => 'Esperando cliente',
            'color' => 'yellow',
        ])->assertRedirect();

        $state = TaskState::where('project_id', $project->id)->where('slug', 'esperando-cliente')->firstOrFail();
        $this->assertFalse($state->is_final);

        // El empleado puede mover a un estado no final personalizado.
        $employee = $this->makeUser(RoleName::Employee);
        $project->members()->attach($employee->employee->id, [
            'role_in_project' => 'Miembro',
            'assigned_at' => now()->toDateString(),
            'status' => 'active',
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $employee->employee->id,
            'status_id' => TaskState::where('project_id', $project->id)->where('slug', 'en-progreso')->first()->id,
        ]);

        $this->actingAs($employee)
            ->patchJson(route('tasks.status.update', $task), ['status_id' => $state->id])
            ->assertOk();

        $this->assertSame('esperando-cliente', $task->refresh()->state->slug);
    }

    public function test_employee_cannot_manage_states(): void
    {
        $employee = $this->makeUser(RoleName::Employee);
        $project = Project::factory()->create();

        $this->actingAs($employee)->get(route('projects.statuses.index', $project))->assertForbidden();
        $this->actingAs($employee)->post(route('projects.statuses.store', $project), [
            'name' => 'X',
            'color' => 'gray',
        ])->assertForbidden();
    }

    public function test_dependency_must_belong_to_same_project(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = Project::factory()->create(['responsible_employee_id' => $lead->employee->id]);
        TaskState::seedDefaults($project->id);
        $other = Project::factory()->create();
        TaskState::seedDefaults($other->id);

        $task = Task::factory()->create(['project_id' => $project->id]);
        $foreign = Task::factory()->create(['project_id' => $other->id]);

        $this->actingAs($lead)
            ->post(route('tasks.dependencies.store', $task), ['depends_on_task_id' => $foreign->id])
            ->assertSessionHasErrors('depends_on_task_id');
    }

    public function test_board_columns_are_unique_by_slug(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $project = Project::factory()->create();
        TaskState::seedDefaults($project->id);

        $response = $this->actingAs($admin)->getJson(route('tasks.board', ['project_id' => $project->id]));

        $response->assertOk();
        $slugs = collect($response->json('columns'))->pluck('slug');
        $this->assertSame($slugs->count(), $slugs->unique()->count());
    }

    public function test_move_by_slug_resolves_project_state_and_records_history(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = Project::factory()->create(['responsible_employee_id' => $lead->employee->id]);
        TaskState::seedDefaults($project->id);
        $from = TaskState::where('project_id', $project->id)->where('slug', 'pendiente')->firstOrFail();
        $task = Task::factory()->create(['project_id' => $project->id, 'status_id' => $from->id]);

        $response = $this->actingAs($lead)
            ->patchJson(route('tasks.status.update', $task), ['status_slug' => 'en-revision']);

        $response->assertOk()->assertJsonPath('status_slug', 'en-revision');

        $expected = TaskState::where('project_id', $project->id)->where('slug', 'en-revision')->firstOrFail();
        $this->assertSame($expected->id, $task->refresh()->status_id);
        $this->assertDatabaseHas('task_status_history', [
            'task_id' => $task->id,
            'from_status_id' => $from->id,
            'to_status_id' => $expected->id,
        ]);
    }
}
