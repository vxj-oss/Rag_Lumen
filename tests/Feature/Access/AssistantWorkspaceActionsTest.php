<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Services\WorkspaceAttentionService;
use App\Support\Enums\TaskStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantWorkspaceActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        TaskState::seedDefaults(null);
        config(['rag.completion.groq.api_key' => '']);
        Http::preventStrayRequests();
    }

    private function account(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Employee::factory()->create(['usuario_id' => $user->id]);

        return $user->refresh();
    }

    private function project(User $lead): Project
    {
        $project = Project::factory()->create(['nombre' => 'Portal del equipo', 'estado' => 'in_progress', 'empleado_responsable_id' => $lead->employee->id, 'fecha_inicio' => now()->subDays(20), 'fecha_fin_estimada' => now()->subDay()]);
        $project->members()->attach($lead->employee->id, ['rol_en_proyecto' => 'developer', 'asignado_en' => now()->toDateString(), 'estado' => 'active']);

        return $project;
    }

    public function test_current_dashboard_attention_is_visible_even_without_saved_notifications(): void
    {
        $admin = $this->account('administrator');
        $project = $this->project($admin);
        $this->assertSame(0, $admin->notifications()->count());
        $items = app(WorkspaceAttentionService::class)->forUser($admin);
        $this->assertCount(1, $items);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee($project->nombre);
        $this->get(route('alerts.index'))->assertOk()->assertViewHas('attentionItems', fn ($rows) => count($rows) === count($items))
            ->assertViewHas('unreadCount', 0)->assertSee($project->nombre)->assertSee('Notificaciones guardadas');
    }

    public function test_attention_for_employee_excludes_other_people_and_reading_does_not_clear_conditions(): void
    {
        $user = $this->account('employee');
        $other = $this->account('project_lead');
        $project = $this->project($other);
        $state = TaskState::resolveForStatus($project->id, TaskStatus::Pending);
        Task::factory()->create(['proyecto_id' => $project->id, 'asignado_a' => $user->employee->id, 'estado' => 'pending', 'estado_id' => $state->id, 'fecha_vencimiento' => now()->addDay()]);
        Task::factory()->create(['proyecto_id' => $project->id, 'asignado_a' => $other->employee->id, 'estado' => 'pending', 'estado_id' => $state->id, 'fecha_vencimiento' => now()->addDay()]);
        $this->actingAs($user)->post(route('alerts.read-all'))->assertForbidden();
        $this->assertCount(1, app(WorkspaceAttentionService::class)->forUser($user));
    }

    public function test_leader_gets_editable_draft_and_saves_with_existing_activity_validation(): void
    {
        $lead = $this->account('project_lead');
        $project = $this->project($lead);
        $response = $this->actingAs($lead)->postJson(route('rag-query.store'), ['question' => 'Crea una actividad "Revisar portal" con prioridad alta. Tareas: Revisar diseño; Validar formulario', 'project_id' => $project->id])->assertOk()
            ->assertJsonPath('action.type', 'create_activity')->assertJsonPath('action.draft.title', 'Revisar portal')
            ->assertJsonPath('action.draft.priority', 'high')->assertJsonCount(2, 'action.draft.tasks');
        $this->assertSame(0, Task::count());
        $action = $response->json('action');
        $this->postJson(route('tasks.store'), ['proyecto_id' => $project->id, 'asignado_a' => $lead->employee->id,
            'titulo' => $action['draft']['title'], 'estado_id' => $action['projects'][0]['state_id'], 'prioridad' => 'high',
            'tareas' => [['titulo' => 'Revisar diseño'], ['titulo' => 'Validar formulario']]])->assertCreated()->assertJsonPath('task.title', 'Revisar portal');
        $activity = Task::activities()->firstOrFail();
        $this->assertSame('Revisar portal', $activity->titulo);
        $this->assertSame(2, $activity->subtareas()->count());
        $this->assertSame($lead->id, $activity->creado_por);
    }

    public function test_employee_cannot_create_activities_from_chat_or_direct_request(): void
    {
        $user = $this->account('employee');
        $this->actingAs($user)->postJson(route('rag-query.store'), ['question' => 'Crea una actividad nueva'])->assertOk()
            ->assertSee('Tu rol no permite crear actividades')->assertJsonPath('action', null);
        $this->postJson(route('tasks.store'), ['titulo' => 'No permitido'])->assertForbidden();
        $this->assertSame(0, Task::count());
    }

    public function test_portfolio_analysis_covers_accessible_projects_and_hides_private_projects(): void
    {
        $user = $this->account('project_lead');
        $lead = $this->account('project_lead');
        $visible = $this->project($user);
        Task::factory()->create(['proyecto_id' => $visible->id, 'asignado_a' => $user->employee->id]);
        Project::factory()->create(['nombre' => 'Secreto financiero', 'empleado_responsable_id' => $lead->employee->id]);
        $this->actingAs($user)->postJson(route('rag-query.store'), ['question' => 'Analiza todos los proyectos'])->assertOk()
            ->assertSee('1 proyecto dentro de tu acceso')->assertSee($visible->nombre)->assertDontSee('Secreto financiero')->assertJsonCount(1, 'sources');
        Http::assertNothingSent();
    }

    public function test_leader_cannot_submit_a_draft_to_another_project_or_nonmember(): void
    {
        $lead = $this->account('project_lead');
        $other = $this->account('project_lead');
        $project = $this->project($lead);
        $private = $this->project($other);
        $payload = ['proyecto_id' => $private->id, 'asignado_a' => $other->employee->id, 'titulo' => 'No autorizado', 'prioridad' => 'medium', 'estado_id' => TaskState::resolveForStatus($private->id, TaskStatus::Pending)->id, 'tareas' => [['titulo' => 'Tarea']]];
        $this->actingAs($lead)->postJson(route('tasks.store'), $payload)->assertForbidden();
        $payload['proyecto_id'] = $project->id;
        $this->postJson(route('tasks.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('asignado_a');
    }

    public function test_closed_projects_do_not_generate_current_operational_attention(): void
    {
        $admin = $this->account('administrator');
        $project = $this->project($admin);
        $project->update(['estado' => 'cancelled']);
        $this->assertCount(0, app(WorkspaceAttentionService::class)->forUser($admin));
        $project->update(['estado' => 'completed']);
        $this->assertCount(0, app(WorkspaceAttentionService::class)->forUser($admin));
    }
}
