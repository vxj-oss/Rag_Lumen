<?php

namespace Tests\Feature\Access;

use App\Models\Area;
use App\Models\Employee;
use App\Models\EmployeeSanction;
use App\Models\Falta;
use App\Models\Project;
use App\Models\TerminosReferencia;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class WorkforceRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['rag.completion.groq.api_key' => '']);
        Http::preventStrayRequests();
    }

    private function account(string $role, string $specialty = 'backend'): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);
        Employee::factory()->create(['usuario_id' => $u->id, 'especialidad' => $specialty]);

        return $u->refresh();
    }

    public function test_worker_only_sees_personal_modules_and_cannot_change_password(): void
    {
        $worker = $this->account('employee');
        $this->actingAs($worker)->get(route('dashboard'))->assertRedirect(route('rag-query.index'));
        foreach (['projects.index', 'clients.index', 'employees.index', 'areas.index', 'activity.index', 'alerts.index', 'users.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->get(route('employees.record'))->assertOk()->assertSee('Mi expediente')->assertDontSee('Usuarios y accesos')->assertDontSee('>Dashboard<', false);
        $this->put(route('password.update'), ['current_password' => 'password', 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'])->assertForbidden();
        $this->assertTrue(Hash::check('password', $worker->fresh()->password));
        $this->get(route('workspaces.show', 'marketing'))->assertForbidden();
        $this->get(route('normativa.index', ['area' => 'marketing']))->assertForbidden();
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('name="current_password"', false);
    }

    public function test_worker_cannot_use_reset_token_to_change_password(): void
    {
        $worker = $this->account('employee');
        $token = Password::createToken($worker);
        $this->post(route('password.store'), ['email' => $worker->email, 'token' => $token, 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'])->assertForbidden();
        $this->assertTrue(Hash::check('password', $worker->fresh()->password));
    }

    public function test_lead_assigns_permanent_password_only_to_workers_in_their_area(): void
    {
        $lead = $this->account('project_lead');
        $worker = $this->account('employee');
        $outside = $this->account('employee', 'marketing');
        $admin = $this->account('administrator');
        $payload = ['current_password' => 'password', 'password' => 'PermanentPassword123!', 'password_confirmation' => 'PermanentPassword123!'];
        $this->actingAs($lead)->put(route('employees.access.update', $worker->employee), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(Hash::check($payload['password'], $worker->fresh()->password));
        $this->assertFalse($worker->fresh()->force_password_change);
        $this->assertEquals(2, $worker->fresh()->auth_version);
        $this->put(route('employees.access.update', $outside->employee), $payload)->assertForbidden();
        $this->put(route('employees.access.update', $admin->employee), $payload)->assertForbidden();
        $this->get(route('workspaces.show', 'marketing'))->assertForbidden();
    }

    public function test_manager_creates_projects_from_natural_language_draft(): void
    {
        $manager = $this->account('manager');
        $response = $this->actingAs($manager)->postJson(route('rag-query.store'), ['question' => 'créame un proyecto llamado campaña de marketing'])->assertOk()->assertJsonPath('action.type', 'create_project')->assertJsonPath('action.draft.name', 'campaña de marketing');
        $draft = $response->json('action.draft');
        $this->assertSame(0, Project::count());
        $this->postJson(route('projects.store'), ['codigo' => $draft['code'], 'nombre' => $draft['name'], 'tipo' => 'digital_marketing', 'fecha_inicio' => now()->toDateString(), 'fecha_fin_estimada' => now()->addDays(10)->toDateString(), 'estado' => 'planning', 'prioridad' => 'medium', 'empleado_gerente_id' => $manager->employee->id])->assertCreated()->assertJsonPath('project.title', 'campaña de marketing');
        Http::assertNothingSent();
    }

    public function test_worker_and_lead_cannot_create_projects_through_chat_or_direct_request(): void
    {
        foreach (['employee', 'project_lead'] as $role) {
            $u = $this->account($role);
            $this->actingAs($u)->postJson(route('rag-query.store'), ['question' => 'Crea un proyecto nuevo'])->assertOk()->assertJsonPath('action', null);
            $this->postJson(route('projects.store'), [])->assertForbidden();
        }
    }

    public function test_lists_have_five_desktop_records_and_two_mobile_records_with_real_pagination(): void
    {
        $admin = $this->account('administrator');
        Project::factory()->count(7)->create();
        $this->actingAs($admin)->get(route('projects.index'))->assertOk()->assertViewHas('projects', fn ($p) => $p->count() === 5 && $p->total() === 7 && $p->lastPage() === 2);
        $this->get(route('projects.index', ['per_page' => 2]))->assertOk()->assertViewHas('projects', fn ($p) => $p->count() === 2 && $p->total() === 7 && $p->lastPage() === 4);
        $this->get(route('projects.index', ['per_page' => 2, 'page' => 2]))->assertOk()->assertViewHas('projects', fn ($p) => $p->count() === 2 && $p->currentPage() === 2);
    }

    public function test_workers_only_read_their_sanctions_and_leaders_edit_only_their_area(): void
    {
        $lead = $this->account('project_lead');
        $worker = $this->account('employee');
        $outside = $this->account('employee', 'marketing');
        $sanction = EmployeeSanction::create(['empleado_id' => $worker->employee->id, 'aplicado_por' => $lead->id, 'fecha_hechos' => now(), 'hechos' => 'Incumplimiento registrado']);
        $other = EmployeeSanction::create(['empleado_id' => $outside->employee->id, 'aplicado_por' => $lead->id, 'fecha_hechos' => now(), 'hechos' => 'Hechos de otra área']);
        $this->actingAs($worker)->get(route('employees.record'))->assertOk()->assertSee('Incumplimiento registrado')->assertDontSee('Hechos de otra área');
        $payload = ['hechos' => 'Corregido por responsable', 'fecha_hechos' => now()->toDateString()];
        $this->put(route('sanctions.update', $sanction), $payload)->assertForbidden();
        $this->get(route('sanctions.download', $other))->assertForbidden();
        $this->actingAs($lead)->put(route('sanctions.update', $sanction), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->put(route('sanctions.update', $other), $payload)->assertForbidden();
        $this->get(route('employees.show', $worker->employee))->assertOk()->assertSee('Asignar contraseña permanente');
    }

    public function test_leader_records_sanction_and_worker_can_download_only_personal_documents(): void
    {
        $lead = $this->account('project_lead');
        $worker = $this->account('employee');
        $other = $this->account('employee', 'marketing');
        $this->actingAs($lead)->post(route('employees.breach-notice', $worker->employee), ['fecha_hechos' => now()->toDateString(), 'hechos' => 'Registro verificable de prueba'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $sanction = EmployeeSanction::firstOrFail();
        $this->assertEquals($worker->employee->id, $sanction->empleado_id);
        $this->actingAs($worker)->get(route('sanctions.download', $sanction))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('employees.contract', $worker->employee))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('employees.contract', $other->employee))->assertForbidden();
    }

    public function test_leader_edits_area_sanction_manual_but_not_tdr_or_other_areas(): void
    {
        $lead = $this->account('project_lead');
        $payload = ['codigo' => 'TEST-WF-1', 'titulo' => 'Falta de prueba', 'descripcion' => 'Descripción de prueba', 'gravedad' => 'leve', 'area' => 'software', 'sancion' => 'Amonestación', 'activa' => true];
        $this->actingAs($lead)->post(route('normativa.faltas.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $falta = Falta::where('codigo', 'TEST-WF-1')->firstOrFail();
        $this->put(route('normativa.faltas.update', $falta), [...$payload, 'titulo' => 'Corregida'])->assertSessionHasNoErrors();
        $this->post(route('normativa.faltas.store'), [...$payload, 'codigo' => 'TEST-WF-2', 'area' => 'marketing'])->assertForbidden();
        $tdr = TerminosReferencia::porArea('software');
        $this->put(route('normativa.tdr.update', $tdr), ['nombre' => 'Cambio', 'contenido' => 'Contenido'])->assertForbidden();
        $this->get(route('normativa.index'))->assertOk()->assertSee('Nueva falta')->assertDontSee('Editar TDR');
    }

    public function test_assistant_module_counts_match_records_and_enforce_roles(): void
    {
        $admin = $this->account('administrator');
        Area::create(['nombre' => 'Diseño', 'activa' => true]);
        Area::create(['nombre' => 'Contenido', 'activa' => true]);
        $this->actingAs($admin)->postJson(route('rag-query.store'), ['question' => 'Cuántas áreas hay registradas'])->assertOk()->assertJsonPath('answer', fn ($answer) => str_contains($answer, '2 áreas registradas accesibles'));
        $worker = $this->account('employee');
        $this->actingAs($worker)->postJson(route('rag-query.store'), ['question' => 'Cuántos usuarios hay'])->assertOk()->assertSee('Tu rol no tiene acceso');
        $this->postJson(route('rag-query.store'), ['question' => 'Muéstrame mi contrato'])->assertOk()->assertJsonPath('sources.0.url', route('employees.contract', $worker->employee));
        Http::assertNothingSent();
    }

    public function test_administrative_queries_respect_module_permissions(): void
    {
        $admin = $this->account('administrator');
        $this->actingAs($admin)->postJson(route('rag-query.store'), ['question' => 'Muéstrame el estado del sistema'])->assertOk()->assertJsonPath('sources.0.url', route('system.index'));
        $this->postJson(route('rag-query.store'), ['question' => 'Muéstrame la auditoría'])->assertOk()->assertJsonPath('sources.0.url', route('activity.index'));
        $worker = $this->account('employee');
        $this->actingAs($worker)->postJson(route('rag-query.store'), ['question' => 'Muéstrame la auditoría'])->assertOk()->assertJsonPath('sources', []);
        Http::assertNothingSent();
    }
}
