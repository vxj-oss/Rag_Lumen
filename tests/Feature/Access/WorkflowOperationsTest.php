<?php

namespace Tests\Feature\Access;

use App\Jobs\ProcessRagDocument;
use App\Models\Employee;
use App\Models\Project;
use App\Models\RagDocument;
use App\Models\Task;
use App\Models\User;
use App\RAG\Chunkers\TextChunkerInterface;
use App\RAG\Embeddings\EmbeddingServiceInterface;
use App\RAG\Services\TextNormalizer;
use App\RAG\VectorStores\VectorStoreInterface;
use App\Services\TaskWorkflowNotifier;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function account(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_admin_can_restore_archived_records_and_other_roles_cannot(): void
    {
        $project = Project::factory()->create();
        $project->delete();
        $this->actingAs($this->account('employee'))->post(route('archive.restore', ['kind' => 'projects', 'id' => $project->id]))->assertForbidden();
        $this->actingAs($this->account('administrator'))->get(route('archive.index'))->assertOk()->assertSee($project->nombre);
        $this->post(route('archive.restore', ['kind' => 'projects', 'id' => $project->id]))->assertSessionHasNoErrors();
        $this->assertNotNull(Project::find($project->id));
    }

    public function test_comments_require_task_access_and_are_escaped(): void
    {
        $user = $this->account('employee');
        Employee::factory()->create(['usuario_id' => $user->id]);
        $task = Task::factory()->create(['asignado_a' => Employee::factory()->create()->id]);
        $this->actingAs($user)->post(route('tasks.comments', $task), ['comment' => 'No autorizado'])->assertForbidden();
        $this->actingAs($this->account('administrator'))->post(route('tasks.comments', $task), ['comment' => '<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->get(route('tasks.show', $task))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_assignments_are_notified_once_and_respect_in_app_preferences(): void
    {
        $user = $this->account('employee');
        $employee = Employee::factory()->create(['usuario_id' => $user->id]);
        $task = Task::factory()->create(['asignado_a' => $employee->id]);
        app(TaskWorkflowNotifier::class)->schedule($task, 'assigned');
        app(TaskWorkflowNotifier::class)->schedule($task, 'assigned');
        $this->assertSame(1, $user->notifications()->count());
        $user->update(['notify_in_app' => false]);
        Task::factory()->create(['asignado_a' => $employee->id]);
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_document_processing_does_not_call_paid_embedding_providers(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test.txt', 'Cada entrega debe tener evidencia y aprobación del responsable.');
        $document = RagDocument::create(['titulo' => 'Guía', 'nombre_archivo' => 'test.txt', 'ruta_archivo' => 'documents/test.txt', 'tipo_mime' => 'text/plain', 'tipo_origen' => 'txt', 'estado' => 'pending']);
        $embedding = $this->mock(EmbeddingServiceInterface::class);
        $embedding->shouldNotReceive('embedMany');
        $vectors = $this->mock(VectorStoreInterface::class);
        $vectors->shouldNotReceive('store');
        (new ProcessRagDocument($document->id))->handle(app(TextNormalizer::class), app(TextChunkerInterface::class), $embedding, $vectors);
        $this->assertSame('processed', $document->fresh()->estado->value);
        $this->assertGreaterThan(0, $document->chunks()->count());
    }

    public function test_activity_counts_do_not_double_count_child_tasks(): void
    {
        $admin = $this->account('administrator');
        $project = Project::factory()->create();
        $parent = Task::factory()->create(['proyecto_id' => $project->id, 'estado' => 'pending']);
        Task::factory()->count(3)->create(['proyecto_id' => $project->id, 'tarea_padre_id' => $parent->id, 'estado' => 'pending']);
        $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->assertSame(1, $response->viewData('summary')['pending_tasks']);
        $this->assertSame(3,Task::workItems()->count());
    }
}
