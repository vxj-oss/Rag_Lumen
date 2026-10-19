<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\Project;
use App\Models\RagDocument;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\RoleName;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        TaskState::seedDefaults(null);
    }

    private function user(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Employee::factory()->create(['usuario_id' => $user->id, 'correo' => $user->email]);

        return $user->refresh();
    }

    public function test_workspace_search_requires_authentication_and_validates_the_query(): void
    {
        $this->getJson(route('workspace.search', ['q' => 'Proyecto']))->assertUnauthorized();
        $this->actingAs($this->user(RoleName::Employee))->getJson(route('workspace.search', ['q' => 'a']))->assertUnprocessable();
    }

    public function test_search_finds_records_for_admin_without_exposing_private_fields(): void
    {
        $admin = $this->user(RoleName::Administrator);
        $project = Project::factory()->create(['nombre' => 'Portal Aurora']);
        Task::factory()->create(['proyecto_id' => $project->id, 'titulo' => 'Auditar Aurora']);
        Employee::factory()->create(['nombres' => 'Aurora', 'documento_identidad' => '12345678']);

        $response = $this->actingAs($admin)->getJson(route('workspace.search', ['q' => 'Aurora']));
        $response->assertOk()->assertJsonCount(3, 'results')
            ->assertJsonFragment(['title' => 'Portal Aurora'])
            ->assertJsonFragment(['title' => 'Auditar Aurora'])
            ->assertDontSee('12345678');
    }

    public function test_employee_search_never_returns_other_employees_or_unassigned_tasks(): void
    {
        $employee = $this->user(RoleName::Employee);
        $project = Project::factory()->create(['nombre' => 'Horizonte con acceso', 'empleado_responsable_id' => Employee::factory()->create()->id]);
        Project::factory()->create(['nombre' => 'Horizonte privado', 'empleado_responsable_id' => Employee::factory()->create()->id]);
        Task::factory()->create(['proyecto_id' => $project->id, 'titulo' => 'Horizonte propio', 'asignado_a' => $employee->employee->id]);
        Task::factory()->create(['proyecto_id' => $project->id, 'titulo' => 'Horizonte ajeno', 'asignado_a' => Employee::factory()->create()->id]);
        Employee::factory()->create(['nombres' => 'Horizonte']);

        $this->actingAs($employee)->getJson(route('workspace.search', ['q' => 'Horizonte']))
            ->assertOk()->assertJsonCount(1, 'results')->assertJsonFragment(['title' => 'Horizonte propio'])
            ->assertDontSee('Horizonte con acceso')->assertDontSee('Horizonte ajeno')->assertDontSee('Horizonte privado');
    }

    public function test_leader_search_is_limited_to_their_projects_and_visible_people(): void
    {
        $leader = $this->user(RoleName::ProjectLead);
        $own = Project::factory()->create(['nombre' => 'Aurora equipo', 'empleado_responsable_id' => $leader->employee->id]);
        $other = Project::factory()->create(['nombre' => 'Aurora privado', 'empleado_responsable_id' => Employee::factory()->create()->id]);
        $worker = Employee::factory()->create(['nombres' => 'Aurora', 'especialidad' => $leader->employee->especialidad]);
        Task::factory()->create(['proyecto_id' => $own->id, 'asignado_a' => $worker->id, 'titulo' => 'Aurora entrega']);
        Task::factory()->create(['proyecto_id' => $other->id, 'asignado_a' => Employee::factory()->create()->id, 'titulo' => 'Aurora secreta']);

        $this->actingAs($leader)->getJson(route('workspace.search', ['q' => 'Aurora']))
            ->assertOk()->assertJsonCount(3, 'results')->assertJsonFragment(['title' => 'Aurora equipo'])
            ->assertDontSee('Aurora privado')->assertDontSee('Aurora secreta');
    }

    public function test_source_reader_respects_project_scope_and_escapes_extracted_text(): void
    {
        $admin = $this->user(RoleName::Administrator);
        $employee = $this->user(RoleName::Employee);
        $project = Project::factory()->create(['empleado_responsable_id' => $admin->employee->id]);
        $document = RagDocument::create([
            'proyecto_id' => $project->id, 'subido_por' => $admin->id, 'titulo' => 'Fuente de prueba',
            'nombre_archivo' => 'prueba.txt', 'ruta_archivo' => 'private/prueba.txt', 'tipo_mime' => 'text/plain', 'tipo_origen' => 'txt',
        ]);
        $document->chunks()->create(['indice_fragmento' => 0, 'contenido' => '<script>unsafe()</script> Texto verificable']);

        $this->actingAs($employee)->get(route('rag-documents.source', $document))->assertForbidden();
        $this->actingAs($admin)->get(route('rag-documents.source', $document))->assertOk()
            ->assertSee('Texto verificable')->assertDontSee('<script>unsafe()</script>', false);
    }

    public function test_manager_search_includes_global_projects_and_tasks(): void
    {
        $manager = $this->user(RoleName::Manager);
        $own = Project::factory()->create(['nombre' => 'Aurora propio', 'empleado_gerente_id' => $manager->employee->id]);
        $other = Project::factory()->create(['nombre' => 'Aurora externo', 'empleado_gerente_id' => null]);
        Task::factory()->create(['proyecto_id' => $own->id, 'titulo' => 'Aurora entrega propia']);
        Task::factory()->create(['proyecto_id' => $other->id, 'titulo' => 'Aurora entrega externa', 'asignado_a' => Employee::factory()->create()->id]);

        $this->actingAs($manager)->getJson(route('workspace.search', ['q' => 'Aurora']))
            ->assertOk()->assertJsonFragment(['title' => 'Aurora propio'])->assertJsonFragment(['title' => 'Aurora entrega propia'])
            ->assertJsonFragment(['title' => 'Aurora externo'])->assertJsonFragment(['title' => 'Aurora entrega externa']);
    }

    public function test_project_tabs_and_task_preview_render_with_current_permissions(): void
    {
        $admin = $this->user(RoleName::Administrator);
        $project = Project::factory()->create();
        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()
            ->assertSee('project-panel-tasks')->assertSee('project-panel-documents')->assertSee('project-panel-history');
        $this->get(route('tasks.index'))->assertOk()->assertSee('Vista rápida de actividad')->assertSee('Guardar vista');
    }

    public function test_tasks_due_today_are_not_overdue_and_have_a_clear_date_label(): void
    {
        $admin = $this->user(RoleName::Administrator);
        $task = Task::factory()->create([
            'fecha_vencimiento' => now()->toDateString(),
            'estado_id' => TaskState::whereNull('proyecto_id')->where('slug', 'pendiente')->value('id'),
        ]);
        $this->assertFalse($task->isOverdue());
        $this->actingAs($admin)->getJson(route('tasks.board'))
            ->assertOk()->assertJsonPath('cards.0.due.label', 'Vence hoy')->assertJsonPath('kpis.overdue', 0);
    }
}
