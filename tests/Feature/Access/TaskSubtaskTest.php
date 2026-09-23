<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\RoleName;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskSubtaskTest extends TestCase
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
        Employee::factory()->create(['usuario_id' => $user->id, 'correo' => $user->email]);

        return $user;
    }

    private function projectWithStates(?int $responsibleId = null): Project
    {
        $project = Project::factory()->create(['empleado_responsable_id' => $responsibleId]);
        TaskState::seedDefaults($project->id);

        return $project;
    }

    private function stateId(Project $project, string $slug): int
    {
        return TaskState::where('proyecto_id', $project->id)->where('slug', $slug)->firstOrFail()->id;
    }

    public function test_recording_subtask_progress_rolls_up_to_parent(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = $this->projectWithStates($lead->employee->id);
        $pendiente = $this->stateId($project, 'pendiente');

        $parent = Task::factory()->create([
            'proyecto_id' => $project->id,
            'estado_id' => $pendiente,
            'estado' => 'pending',
            'porcentaje_progreso' => 0,
            'horas_estimadas' => 10,
        ]);

        $subA = Task::factory()->create([
            'proyecto_id' => $project->id,
            'tarea_padre_id' => $parent->id,
            'estado_id' => $pendiente,
            'estado' => 'pending',
            'porcentaje_progreso' => 0,
            'horas_estimadas' => 10,
        ]);

        $subB = Task::factory()->create([
            'proyecto_id' => $project->id,
            'tarea_padre_id' => $parent->id,
            'estado_id' => $pendiente,
            'estado' => 'pending',
            'porcentaje_progreso' => 0,
            'horas_estimadas' => 10,
        ]);

        $this->actingAs($lead)
            ->post(route('tasks.progress.store', $subA), ['new_percentage' => 100])
            ->assertRedirect();

        $this->assertSame(50, $parent->refresh()->porcentaje_progreso);
        $this->assertSame('en-progreso', $parent->state->slug);

        $this->actingAs($lead)
            ->post(route('tasks.progress.store', $subB), ['new_percentage' => 100])
            ->assertRedirect();

        $parent->refresh();
        $this->assertSame(100, $parent->porcentaje_progreso);
        $this->assertSame('completada', $parent->state->slug);
        $this->assertNotNull($parent->completado_en);
    }

    public function test_cannot_record_progress_directly_on_a_task_with_subtasks(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = $this->projectWithStates($lead->employee->id);
        $pendiente = $this->stateId($project, 'pendiente');

        $parent = Task::factory()->create(['proyecto_id' => $project->id, 'estado_id' => $pendiente, 'estado' => 'pending']);
        Task::factory()->create(['proyecto_id' => $project->id, 'tarea_padre_id' => $parent->id, 'estado_id' => $pendiente, 'estado' => 'pending']);

        $this->actingAs($lead)
            ->post(route('tasks.progress.store', $parent), ['new_percentage' => 50])
            ->assertForbidden();
    }

    public function test_subtasks_are_excluded_from_the_board_but_the_parent_is_not(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = $this->projectWithStates($lead->employee->id);
        $pendiente = $this->stateId($project, 'pendiente');

        $parent = Task::factory()->create(['proyecto_id' => $project->id, 'estado_id' => $pendiente, 'estado' => 'pending']);
        $sub = Task::factory()->create(['proyecto_id' => $project->id, 'tarea_padre_id' => $parent->id, 'estado_id' => $pendiente, 'estado' => 'pending']);

        $response = $this->actingAs($lead)->getJson(route('tasks.board'))->assertOk();
        $titles = collect($response->json('cards'))->pluck('title');

        $this->assertTrue($titles->contains($parent->titulo));
        $this->assertFalse($titles->contains($sub->titulo));
    }

    public function test_cannot_delete_a_task_that_still_has_subtasks(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = $this->projectWithStates($lead->employee->id);
        $pendiente = $this->stateId($project, 'pendiente');

        $parent = Task::factory()->create(['proyecto_id' => $project->id, 'estado_id' => $pendiente, 'estado' => 'pending']);
        Task::factory()->create(['proyecto_id' => $project->id, 'tarea_padre_id' => $parent->id, 'estado_id' => $pendiente, 'estado' => 'pending']);

        $this->actingAs($lead)
            ->delete(route('tasks.destroy', $parent))
            ->assertRedirect();

        $this->assertNull($parent->fresh()->deleted_at);
    }

    public function test_a_task_cannot_become_its_own_ancestor(): void
    {
        $lead = $this->makeUser(RoleName::ProjectLead);
        $project = $this->projectWithStates($lead->employee->id);
        $pendiente = $this->stateId($project, 'pendiente');

        $grandparent = Task::factory()->create(['proyecto_id' => $project->id, 'estado_id' => $pendiente, 'estado' => 'pending']);
        $parent = Task::factory()->create(['proyecto_id' => $project->id, 'tarea_padre_id' => $grandparent->id, 'estado_id' => $pendiente, 'estado' => 'pending']);

        $response = $this->actingAs($lead)->put(route('tasks.update', $grandparent), [
            'titulo' => $grandparent->titulo,
            'prioridad' => $grandparent->prioridad->value,
            'tarea_padre_id' => $parent->id,
        ]);

        $response->assertSessionHasErrors('tarea_padre_id');
        $this->assertNull($grandparent->fresh()->tarea_padre_id);
    }
}
