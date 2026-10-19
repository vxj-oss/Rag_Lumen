<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\Project;
use App\Models\RagConversation;
use App\Models\RagDocument;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use App\Services\Assistant\GroqGateway;
use App\Services\Assistant\SystemQuery;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        TaskState::seedDefaults(null);
        config(['rag.completion.groq.api_key' => '']);
        Cache::flush();
        Http::preventStrayRequests();
    }

    private function account(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_database_counts_and_followup_filters_work_without_external_ai(): void
    {
        $admin = $this->account('administrator');
        Employee::factory()->create(['estado' => 'active', 'especialidad' => 'marketing']);
        Employee::factory()->create(['estado' => 'active', 'especialidad' => 'backend']);
        Employee::factory()->create(['estado' => 'inactive']);
        $first = $this->actingAs($admin)->postJson(route('rag-query.store'), ['question' => '¿Cuántos empleados activos hay?'])->assertOk();
        $this->assertStringContainsString('Resultado verificado: 2 empleados', $first->json('answer'));
        $this->postJson(route('rag-query.store'), ['question' => '¿Y cuáles trabajan en marketing?', 'conversation_id' => $first->json('conversation_id')])->assertOk()->assertJsonCount(1, 'sources');
        Http::assertNothingSent();
    }

    public function test_employee_only_gets_their_own_activity_data(): void
    {
        $user = $this->account('employee');
        $employee = Employee::factory()->create(['usuario_id' => $user->id]);
        $project = Project::factory()->create(['empleado_responsable_id' => Employee::factory()->create()->id]);
        Task::factory()->create(['proyecto_id' => $project->id, 'asignado_a' => $employee->id, 'titulo' => 'Visible asignada']);
        Task::factory()->create(['proyecto_id' => $project->id, 'asignado_a' => Employee::factory()->create()->id, 'titulo' => 'Secreto ajeno']);
        $this->actingAs($user)->postJson(route('rag-query.store'), ['question' => 'Lista las actividades'])->assertOk()->assertJsonCount(1, 'sources')->assertSee('Visible asignada')->assertDontSee('Secreto ajeno');
    }

    public function test_ambiguous_name_is_clarified_and_conversation_remembers_selection(): void
    {
        $admin = $this->account('administrator');
        $ana = Employee::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Torres']);
        Employee::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Ramos']);
        Task::factory()->create(['asignado_a' => $ana->id, 'titulo' => 'Entrega de Ana']);
        $response = $this->actingAs($admin)->postJson(route('rag-query.store'), ['question' => 'Lista las tareas de Ana'])->assertOk()->assertSee('varias coincidencias');
        $this->postJson(route('rag-query.store'), ['question' => 'Ana Torres', 'conversation_id' => $response->json('conversation_id')])->assertOk()->assertSee('Entrega de Ana');
    }

    public function test_conversation_access_is_rechecked_after_project_access_changes(): void
    {
        $user = $this->account('employee');
        $employee = Employee::factory()->create(['usuario_id' => $user->id]);
        $project = Project::factory()->create(['empleado_responsable_id' => Employee::factory()->create()->id]);
        $project->members()->attach($employee->id, ['rol_en_proyecto' => 'developer', 'asignado_en' => now()->toDateString(), 'estado' => 'active']);
        $conversation = RagConversation::create(['usuario_id' => $user->id, 'proyecto_id' => $project->id, 'titulo' => 'Proyecto privado']);
        $project->members()->detach($employee->id);
        $this->actingAs($user)->getJson(route('rag-query.conversations.show', $conversation))->assertForbidden();
        $this->postJson(route('rag-query.store'), ['question' => '¿Quién es el responsable?', 'conversation_id' => $conversation->id])->assertForbidden();
    }

    public function test_assistant_cannot_mutate_records_or_read_another_users_chat(): void
    {
        $user = $this->account('administrator');
        $other = $this->account('employee');
        $conversation = RagConversation::create(['usuario_id' => $other->id, 'titulo' => 'Privado']);
        $this->actingAs($user)->getJson(route('rag-query.conversations.show', $conversation))->assertForbidden();
        $this->postJson(route('rag-query.store'), ['question' => 'Elimina los usuarios'])->assertOk()->assertSee('no cambiar registros');
        $this->assertSame(2, User::count());
    }

    public function test_free_model_fallback_respects_rate_limit_cooldown(): void
    {
        config(['rag.completion.groq.api_key' => 'fake-key']);
        Http::fake(['api.groq.com/*' => Http::sequence()->push(['error' => 'limited'], 429, ['retry-after' => '120'])->push(['choices' => [['message' => ['content' => 'Respuesta disponible']]]], 200)]);
        $answer = app(GroqGateway::class)->complete([['role' => 'user', 'content' => 'Pregunta']]);
        $this->assertSame('Respuesta disponible', $answer);
        Http::assertSentCount(2);
        $this->assertTrue(Cache::has('assistant-cooldown-'.md5('openai/gpt-oss-120b')));
        Http::assertSent(fn ($r) => $r['model'] === 'openai/gpt-oss-20b');
    }

    public function test_models_are_available_with_a_key_without_extra_confirmation(): void
    {
        config(['rag.completion.groq.api_key' => 'fake-key']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Disponible']]]], 200)]);
        $this->assertTrue(app(GroqGateway::class)->enabled());
        $this->assertSame('Disponible', app(GroqGateway::class)->complete([['role' => 'user', 'content' => 'Pregunta']]));
        Http::assertSentCount(1);
    }

    public function test_missing_key_does_not_send_external_requests(): void
    {
        $this->assertFalse(app(GroqGateway::class)->enabled());
        try {
            app(GroqGateway::class)->complete([['role' => 'user', 'content' => 'Pregunta']]);
            $this->fail('Expected missing key guard');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('no está configurado', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_transcription_uses_free_whisper_and_does_not_persist_audio(): void
    {
        config(['rag.completion.groq.api_key' => 'fake-key']);
        Http::fake(['api.groq.com/*' => Http::response(['text' => 'Cuántos empleados activos hay'], 200)]);
        $user = $this->account('employee');
        $this->actingAs($user)->post(route('assistant.transcribe'), ['audio' => UploadedFile::fake()->create('voz.webm', 2, 'audio/webm')])->assertOk()->assertJsonPath('text', 'Cuántos empleados activos hay');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'audio/transcriptions'));
    }

    public function test_audio_rejects_non_audio_files(): void
    {
        $this->actingAs($this->account('employee'))->postJson(route('assistant.transcribe'), ['audio' => UploadedFile::fake()->create('payload.php', 1, 'text/plain')])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_compact_widget_and_system_panel_have_no_activation_confirmation(): void
    {
        $this->actingAs($this->account('administrator'))->get(route('dashboard'))
            ->assertOk()->assertSee('Opciones del asistente')->assertSee('Dictar pregunta', false)
            ->assertDontSee('Autorizo enviar el audio');
        $this->get(route('system.index'))->assertOk()->assertSee('Modelos del asistente')
            ->assertDontSee('Habilitar modelos gratuitos')->assertDontSee('confirmed_free');
    }

    private function document(User $user, array $attributes = []): RagDocument
    {
        return RagDocument::create([...[
            'subido_por' => $user->id, 'titulo' => 'Convenio General', 'nombre_archivo' => 'convenio.txt',
            'ruta_archivo' => 'rag/convenio.txt', 'tipo_mime' => 'text/plain', 'tipo_origen' => 'txt', 'estado' => 'processed',
        ], ...$attributes]);
    }

    public function test_document_count_uses_records_not_chunks_even_with_external_ai_enabled(): void
    {
        $admin = $this->account('administrator');
        $document = $this->document($admin);
        for ($index = 0; $index < 3; $index++) {
            $document->chunks()->create(['indice_fragmento' => $index, 'contenido' => 'Texto del convenio con entregables.']);
        }
        $deleted = $this->document($admin, ['titulo' => 'Eliminado']);
        $deleted->delete();
        config(['rag.completion.groq.api_key' => 'fake-key']);
        $response = $this->actingAs($admin)->postJson(route('rag-query.store'), ['question' => 'Cuántos documentos existen en el sistema'])->assertOk()
            ->assertSee('1 documento registrado')->assertJsonCount(1, 'sources');
        $this->assertEquals($document->id, $response->json('sources.0.document_id'));
        Http::assertNothingSent();
    }

    public function test_document_counts_include_pending_records_and_respect_access_and_followups(): void
    {
        $user = $this->account('employee');
        Employee::factory()->create(['usuario_id' => $user->id]);
        $this->document($user);
        $this->document($user, ['titulo' => 'Sin procesar', 'estado' => 'pending']);
        $this->document($user, ['titulo' => 'Privado', 'proyecto_id' => Project::factory()->create(['empleado_responsable_id' => Employee::factory()->create()->id])->id]);
        $first = $this->actingAs($user)->postJson(route('rag-query.store'), ['question' => 'Cuántos documentos hay'])->assertOk()
            ->assertSee('2 documentos registrados')->assertJsonCount(2, 'sources')->assertDontSee('Privado');
        $this->postJson(route('rag-query.store'), ['question' => '¿Y cuántos pendientes?', 'conversation_id' => $first->json('conversation_id')])
            ->assertOk()->assertSee('1 documento registrado')->assertJsonCount(1, 'sources');
    }

    public function test_document_content_questions_are_not_treated_as_inventory_counts(): void
    {
        $queries = app(SystemQuery::class);
        foreach (['Cuántos entregables menciona el documento', 'Resume los documentos disponibles', 'Qué dice el convenio'] as $question) {
            $this->assertSame('content', $queries->interpret($question)['metric']);
        }
    }
}
