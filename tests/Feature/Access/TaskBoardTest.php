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
        Employee::factory()->create(['usuario_id' => $user->id, 'correo' => $user->email]);

        return $user->refresh();
    }

    public function test_board_returns_columns_kpis_and_cards(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $project = Project::factory()->create();
        TaskState::seedDefaults($project->id);
        Task::factory()->create([
            'proyecto_id' => $project->id,
            'estado_id' => TaskState::where('proyecto_id', $project->id)->where('slug', 'pendiente')->first()->id,
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('tasks.board'));

        $response->assertOk()
            ->assertJsonPath('kpis.open', 1)
            ->assertJsonPath('kpis.overdue', 1)
            ->assertJsonCount(6, 'columns')
            ->assertJsonCount(1, 'cards')
            ->assertJsonFragment(['code' => $project->codigo.'-T1']);
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
                'rol_en_proyecto' => 'Miembro',
                'asignado_en' => now()->toDateString(),
                'estado' => 'active',
            ]);
        }

        Task::factory()->create([
            'proyecto_id' => $project->id,
            'asignado_a' => $employee->employee->id,
            'estado_id' => TaskState::where('proyecto_id', $project->id)->where('slug', 'pendiente')->first()->id,
        ]);
        Task::factory()->create([
            'proyecto_id' => $other->id,
            'asignado_a' => Employee::factory()->create()->id,
            'estado_id' => TaskState::where('proyecto_id', $other->id)->where('slug', 'pendiente')->first()->id,
        ]);

        $this->actingAs($employee)->getJson(route('tasks.board', ['project_id' => $project->id]))
            ->assertOk()->assertJsonCount(1, 'cards');

        $this->actingAs($employee)->getJson(route('tasks.board', ['mine' => 1]))
            ->assertOk()->assertJsonCount(1, 'cards');
    }

    public function test_task_code_is_generated_on_create(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $project = Project::factory()->create(['codigo' => 'PRJ-9']);
        TaskState::seedDefaults($project->id);
        $initial = TaskState::where('proyecto_id', $project->id)->where('es_inicial', true)->firstOrFail();

        $this->actingAs($admin)->post(route('tasks.store'), [
            'proyecto_id' => $project->id,
            'titulo' => 'Tarea con código',
            'estado_id' => $initial->id,
            'prioridad' => 'medium',
        ])->assertRedirect();

        $this->assertDatabaseHas('tareas', ['codigo' => 'PRJ-9-T1', 'proyecto_id' => $project->id]);
        $this->assertSame(1, $project->refresh()->contador_tareas);
    }

    public function test_store_rejects_assignee_outside_project(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $project = Project::factory()->create();
        TaskState::seedDefaults($project->id);
        $outsider = Employee::factory()->create();
        $initial = TaskState::where('proyecto_id', $project->id)->where('es_inicial', true)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('tasks.store'), [
            'proyecto_id' => $project->id,
            'titulo' => 'Tarea inválida',
            'asignado_a' => $outsider->id,
            'estado_id' => $initial->id,
            'prioridad' => 'medium',
        ]);

        $response->assertSessionHasErrors('asignado_a');
    }

    public function test_lead_can_manage_project_states(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = Project::factory()->create(['empleado_responsable_id' => $lead->employee->id]);
        TaskState::seedDefaults($project->id);

        $this->actingAs($lead)->get(route('projects.statuses.index', $project))->assertOk();

        $this->actingAs($lead)->post(route('projects.statuses.store', $project), [
            'name' => 'Esperando cliente',
            'color' => 'yellow',
        ])->assertRedirect();

        $state = TaskState::where('proyecto_id', $project->id)->where('slug', 'esperando-cliente')->firstOrFail();
        $this->assertFalse($state->es_final);

        // El empleado puede mover a un estado no final personalizado.
        $employee = $this->makeUser(RoleName::Employee);
        $project->members()->attach($employee->employee->id, [
            'rol_en_proyecto' => 'Miembro',
            'asignado_en' => now()->toDateString(),
            'estado' => 'active',
        ]);
        $task = Task::factory()->create([
            'proyecto_id' => $project->id,
            'asignado_a' => $employee->employee->id,
            'estado_id' => TaskState::where('proyecto_id', $project->id)->where('slug', 'en-progreso')->first()->id,
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
        $project = Project::factory()->create(['empleado_responsable_id' => $lead->employee->id]);
        TaskState::seedDefaults($project->id);
        $other = Project::factory()->create();
        TaskState::seedDefaults($other->id);

        $task = Task::factory()->create(['proyecto_id' => $project->id]);
        $foreign = Task::factory()->create(['proyecto_id' => $other->id]);

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
        $project = Project::factory()->create(['empleado_responsable_id' => $lead->employee->id]);
        TaskState::seedDefaults($project->id);
        $from = TaskState::where('proyecto_id', $project->id)->where('slug', 'pendiente')->firstOrFail();
        $task = Task::factory()->create(['proyecto_id' => $project->id, 'estado_id' => $from->id]);

        $response = $this->actingAs($lead)
            ->patchJson(route('tasks.status.update', $task), ['status_slug' => 'en-revision']);

        $response->assertOk()->assertJsonPath('status_slug', 'en-revision');

        $expected = TaskState::where('proyecto_id', $project->id)->where('slug', 'en-revision')->firstOrFail();
        $this->assertSame($expected->id, $task->refresh()->estado_id);
        $this->assertDatabaseHas('historial_estados_tarea', [
            'tarea_id' => $task->id,
            'estado_origen_id' => $from->id,
            'estado_destino_id' => $expected->id,
        ]);
    }
}
