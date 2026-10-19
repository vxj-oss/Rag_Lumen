<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\RoleName;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskActivityFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $lead;

    private User $worker;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        TaskState::seedDefaults(null);
        Storage::fake('local');

        $this->lead = $this->makeUser(RoleName::ProjectLead);
        $this->worker = $this->makeUser(RoleName::Employee);

        $this->project = Project::factory()->create(['empleado_responsable_id' => $this->lead->employee->id]);
        TaskState::seedDefaults($this->project->id);

        ProjectMember::create([
            'proyecto_id' => $this->project->id,
            'empleado_id' => $this->worker->employee->id,
            'rol_en_proyecto' => 'developer',
            'asignado_en' => now()->toDateString(),
            'estado' => 'active',
        ]);
    }

    private function makeUser(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Employee::factory()->create(['usuario_id' => $user->id, 'correo' => $user->email]);

        return $user;
    }

    private function createActivity(int $tasks = 5): Task
    {
        $state = TaskState::where('proyecto_id', $this->project->id)->where('slug', 'pendiente')->firstOrFail();

        $this->actingAs($this->lead)->post(route('tasks.store'), [
            'proyecto_id' => $this->project->id,
            'titulo' => 'Actividad de prueba',
            'prioridad' => 'medium',
            'estado_id' => $state->id,
            'asignado_a' => $this->worker->employee->id,
            'tareas' => collect(range(1, $tasks))->map(fn ($i) => ['titulo' => "Tarea {$i}", 'descripcion' => "Hacer {$i}"])->all(),
        ])->assertRedirect();

        return Task::where('titulo', 'Actividad de prueba')->firstOrFail();
    }

    private function complete(Task $child): void
    {
        $this->actingAs($this->worker)
            ->post(route('tasks.evidence.store', $child), ['enlaces' => ['https://drive.google.com/e'.$child->id]])
            ->assertRedirect();
    }

    public function test_lead_creates_activity_assigned_to_worker_with_its_tasks(): void
    {
        $activity = $this->createActivity(5);

        $this->assertSame($this->worker->employee->id, $activity->asignado_a);
        $this->assertSame(5, $activity->subtareas()->count());
        $this->assertTrue($activity->subtareas->every(fn (Task $t) => $t->asignado_a === $this->worker->employee->id));
        $this->assertSame('pendiente', $activity->state->slug);
    }

    public function test_activity_requires_at_least_one_task_and_an_assignee(): void
    {
        $state = TaskState::where('proyecto_id', $this->project->id)->where('slug', 'pendiente')->firstOrFail();

        $this->actingAs($this->lead)->post(route('tasks.store'), [
            'proyecto_id' => $this->project->id,
            'titulo' => 'Sin tareas',
            'prioridad' => 'medium',
            'estado_id' => $state->id,
        ])->assertSessionHasErrors(['tareas', 'asignado_a']);
    }

    public function test_number_of_tasks_is_variable_and_progress_splits_evenly(): void
    {
        $activity = $this->createActivity(4);
        [$a, $b] = $activity->subtareas;

        $this->complete($a);
        $this->assertSame(25, $activity->refresh()->porcentaje_progreso);
        $this->assertSame('en-progreso', $activity->state->slug);

        $this->complete($b);
        $this->assertSame(50, $activity->refresh()->porcentaje_progreso);
    }

    public function test_five_tasks_give_twenty_percent_each_and_reach_review_at_100(): void
    {
        $activity = $this->createActivity(5);

        foreach ($activity->subtareas as $i => $child) {
            $this->complete($child);
            $expected = ($i + 1) * 20;
            $this->assertSame($expected, $activity->refresh()->porcentaje_progreso);
        }

        $activity->refresh();
        $this->assertSame('en-revision', $activity->state->slug);
        $this->assertNull($activity->completado_en);
    }

    public function test_task_cannot_be_completed_without_evidence(): void
    {
        $child = $this->createActivity(2)->subtareas->first();

        $this->actingAs($this->worker)
            ->post(route('tasks.evidence.store', $child), ['comentario' => 'listo'])
            ->assertSessionHasErrors('archivos');

        $this->assertSame('pendiente', $child->refresh()->state->slug);
        $this->assertSame(0, $child->evidencias()->count());
    }

    public function test_task_cannot_be_completed_by_changing_status_directly(): void
    {
        $child = $this->createActivity(2)->subtareas->first();
        $completada = TaskState::where('proyecto_id', $this->project->id)->where('slug', 'completada')->firstOrFail();

        $this->actingAs($this->lead)
            ->patchJson(route('tasks.status.update', $child), ['status_id' => $completada->id])
            ->assertStatus(422);

        $this->actingAs($this->lead)->put(route('tasks.update', $child), [
            'titulo' => $child->titulo,
            'prioridad' => 'medium',
            'estado_id' => $completada->id,
        ])->assertSessionHasErrors('estado_id');

        $this->assertNotSame('completada', $child->refresh()->state->slug);
    }

    public function test_evidence_can_be_a_file_an_image_or_a_link_and_records_author_and_date(): void
    {
        $activity = $this->createActivity(3);
        [$a, $b, $c] = $activity->subtareas;

        $this->actingAs($this->worker)->post(route('tasks.evidence.store', $a), [
            'archivos' => [UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf')],
        ])->assertRedirect();
        $this->actingAs($this->worker)->post(route('tasks.evidence.store', $b), [
            'archivos' => [UploadedFile::fake()->image('captura.png')],
        ])->assertRedirect();
        $this->complete($c);

        $this->assertSame('archivo', $a->evidencias->first()->tipo);
        $this->assertSame('captura.png', $b->evidencias->first()->nombre_archivo);
        $link = $c->evidencias->first();
        $this->assertSame('enlace', $link->tipo);
        $this->assertSame($this->worker->id, $link->usuario_id);
        $this->assertNotNull($link->created_at);

        $this->actingAs($this->lead)
            ->get(route('tasks.evidence.download', [$a, $a->evidencias->first()]))
            ->assertOk();
    }

    public function test_activity_is_not_approved_automatically_at_100_and_only_lead_can_approve(): void
    {
        $activity = $this->createActivity(2);
        $activity->subtareas->each(fn (Task $child) => $this->complete($child));

        $this->assertSame('en-revision', $activity->refresh()->state->slug);

        $this->actingAs($this->worker)->post(route('tasks.evidence.approve', $activity))->assertForbidden();
        $this->assertSame('en-revision', $activity->refresh()->state->slug);

        $this->actingAs($this->lead)->post(route('tasks.evidence.approve', $activity))->assertRedirect();

        $activity->refresh();
        $this->assertSame('completada', $activity->state->slug);
        $this->assertNotNull($activity->completado_en);
    }

    public function test_rejection_requires_reason_and_reopens_tasks_keeping_evidence(): void
    {
        $activity = $this->createActivity(2);
        $activity->subtareas->each(fn (Task $child) => $this->complete($child));

        $this->actingAs($this->lead)->post(route('tasks.evidence.reject', $activity), [])->assertSessionHasErrors('motivo');

        $this->actingAs($this->lead)
            ->post(route('tasks.evidence.reject', $activity), ['motivo' => 'Falta detalle en la tarea 1.'])
            ->assertRedirect();

        $activity->refresh();
        $this->assertSame('en-progreso', $activity->state->slug);
        $this->assertSame(0, $activity->porcentaje_progreso);
        $this->assertSame('Falta detalle en la tarea 1.', $activity->motivo_rechazo);
        $this->assertTrue($activity->subtareas->every(fn (Task $t) => $t->state->slug === 'pendiente' && $t->evidencias()->count() === 1));

        $activity->subtareas->each(fn (Task $child) => $this->complete($child));
        $activity->refresh();
        $this->assertSame('en-revision', $activity->state->slug);
    }

    public function test_cannot_submit_evidence_for_an_activity_itself_or_after_review(): void
    {
        $activity = $this->createActivity(1);

        $this->actingAs($this->worker)
            ->post(route('tasks.evidence.store', $activity), ['enlaces' => ['https://drive.google.com/x']])
            ->assertForbidden();

        $child = $activity->subtareas->first();
        $this->complete($child);

        $this->actingAs($this->worker)
            ->post(route('tasks.evidence.store', $child), ['enlaces' => ['https://drive.google.com/y']])
            ->assertForbidden();
    }

    public function test_lead_can_add_and_remove_tasks_and_progress_is_recalculated(): void
    {
        $activity = $this->createActivity(2);
        $this->complete($activity->subtareas->first());
        $this->assertSame(50, $activity->refresh()->porcentaje_progreso);

        $this->actingAs($this->lead)
            ->post(route('tasks.children.store', $activity), ['titulo' => 'Extra', 'descripcion' => 'Algo más'])
            ->assertRedirect();
        $this->assertSame(3, $activity->subtareas()->count());
        $this->assertSame(33, $activity->refresh()->porcentaje_progreso);

        $extra = $activity->subtareas()->where('titulo', 'Extra')->first();
        $this->assertSame($this->worker->employee->id, $extra->asignado_a);

        $this->actingAs($this->lead)->delete(route('tasks.destroy', $extra))->assertRedirect();
        $this->assertSame(2, $activity->subtareas()->count());
        $this->assertSame(50, $activity->refresh()->porcentaje_progreso);

        $this->actingAs($this->worker)
            ->post(route('tasks.children.store', $activity), ['titulo' => 'No permitido'])
            ->assertForbidden();
    }

    public function test_tasks_cannot_be_added_once_activity_is_in_review(): void
    {
        $activity = $this->createActivity(1);
        $this->complete($activity->subtareas->first());

        $this->actingAs($this->lead)
            ->post(route('tasks.children.store', $activity), ['titulo' => 'Tarde'])
            ->assertForbidden();
    }

    public function test_child_tasks_have_no_nesting_and_are_hidden_from_the_board(): void
    {
        $activity = $this->createActivity(2);
        $child = $activity->subtareas->first();

        $this->actingAs($this->lead)
            ->post(route('tasks.children.store', $child), ['titulo' => 'Sub-subtarea'])
            ->assertForbidden();

        $titles = collect($this->actingAs($this->lead)->getJson(route('tasks.board'))->json('cards'))->pluck('title');
        $this->assertTrue($titles->contains('Actividad de prueba'));
        $this->assertFalse($titles->contains($child->titulo));
    }
}
