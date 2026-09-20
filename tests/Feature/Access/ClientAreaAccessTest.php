<?php

namespace Tests\Feature\Access;

use App\Models\Area;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\RoleName;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAreaAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, AreaSeeder::class]);
    }

    private function makeUser(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Employee::factory()->create(['user_id' => $user->id, 'email' => $user->email]);

        return $user->refresh();
    }

    private function clientPayload(): array
    {
        return [
            'name' => 'Cliente de prueba',
            'tax_id' => 'RUC-123',
            'contact_name' => 'Contacto',
            'email' => 'contacto@cliente.test',
            'phone' => '70000000',
            'sector' => 'Retail',
            'status' => 'active',
        ];
    }

    public function test_admin_and_manager_can_crud_clients_lead_read_only_employee_denied(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $manager = $this->makeUser(RoleName::Manager);
        $lead = $this->makeUser(RoleName::ProjectLead);
        $employee = $this->makeUser(RoleName::Employee);

        $this->actingAs($admin)->post(route('clients.store'), $this->clientPayload())->assertRedirect();
        $this->actingAs($manager)->post(route('clients.store'), array_merge($this->clientPayload(), [
            'name' => 'Otro cliente', 'tax_id' => 'RUC-456',
        ]))->assertRedirect();

        $this->actingAs($lead)->post(route('clients.store'), $this->clientPayload())->assertForbidden();
        $this->actingAs($employee)->get(route('clients.index'))->assertForbidden();
        $this->actingAs($lead)->get(route('clients.index'))->assertOk();
    }

    public function test_client_show_includes_budget_and_projects(): void
    {
        $manager = $this->makeUser(RoleName::Manager);
        $client = Client::create($this->clientPayload());
        Project::factory()->create(['client_id' => $client->id, 'budget' => 1000]);
        Project::factory()->create(['client_id' => $client->id, 'budget' => 2500]);

        $response = $this->actingAs($manager)->get(route('clients.show', $client));

        $response->assertOk();
        $response->assertSee('3,500.00');
    }

    public function test_client_cannot_be_deleted_with_projects(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $client = Client::create($this->clientPayload());
        Project::factory()->create(['client_id' => $client->id]);

        $this->actingAs($admin)->delete(route('clients.destroy', $client))->assertRedirect();

        $this->assertNotSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_only_admin_manages_areas(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $manager = $this->makeUser(RoleName::Manager);
        $employee = $this->makeUser(RoleName::Employee);

        $this->actingAs($admin)->post(route('areas.store'), ['name' => 'Nueva área'])->assertRedirect();
        $this->assertDatabaseHas('areas', ['name' => 'Nueva área']);

        $this->actingAs($manager)->post(route('areas.store'), ['name' => 'Otra área'])->assertForbidden();
        $this->actingAs($employee)->get(route('areas.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('areas.index'))->assertOk();
    }

    public function test_area_cannot_be_deleted_with_employees(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $area = Area::where('name', 'Diseño')->first();
        Employee::factory()->create(['area_id' => $area->id]);

        $this->actingAs($admin)->delete(route('areas.destroy', $area))->assertRedirect();

        $this->assertDatabaseHas('areas', ['id' => $area->id]);
    }

    public function test_project_store_links_client_manager_and_areas(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $manager = $this->makeUser(RoleName::Manager);
        $client = Client::create($this->clientPayload());
        $areaIds = Area::take(2)->pluck('id')->all();

        $response = $this->actingAs($admin)->post(route('projects.store'), [
            'code' => 'CLI-001',
            'name' => 'Proyecto con cliente',
            'type' => 'digital_marketing',
            'client_id' => $client->id,
            'manager_employee_id' => $manager->employee->id,
            'area_ids' => $areaIds,
            'start_date' => now()->toDateString(),
            'estimated_end_date' => now()->addMonth()->toDateString(),
            'status' => 'planning',
            'priority' => 'medium',
        ]);

        $response->assertRedirect(route('projects.index'));

        $project = Project::where('code', 'CLI-001')->firstOrFail();
        $this->assertSame($client->id, $project->client_id);
        $this->assertSame($manager->employee->id, $project->manager_employee_id);
        $this->assertEqualsCanonicalizing($areaIds, $project->areas->pluck('id')->all());
    }

    public function test_manager_scope_is_limited_to_own_areas_and_managed(): void
    {
        $manager = $this->makeUser(RoleName::Manager);
        $area = Area::where('name', 'Diseño')->first();
        $manager->employee->update(['area_id' => $area->id]);

        $inScope = Project::factory()->create();
        $inScope->areas()->sync([$area->id]);
        $managed = Project::factory()->create(['manager_employee_id' => $manager->employee->id]);
        $outside = Project::factory()->create();

        $this->actingAs($manager)->get(route('projects.show', $inScope))->assertOk();
        $this->actingAs($manager)->get(route('projects.show', $managed))->assertOk();
        $this->actingAs($manager)->get(route('projects.show', $outside))->assertForbidden();

        $response = $this->actingAs($manager)->get(route('projects.index'));
        $response->assertSee($inScope->name);
        $response->assertSee($managed->name);
        $response->assertDontSee($outside->name);
    }

    public function test_project_scope_data_returns_areas_and_employees(): void
    {
        $admin = $this->makeUser(RoleName::Administrator);
        $area = Area::where('name', 'Diseño')->first();
        $project = Project::factory()->create();
        $project->areas()->sync([$area->id]);
        $member = Employee::factory()->create(['area_id' => $area->id]);
        Employee::factory()->create();

        $response = $this->actingAs($admin)->getJson(route('projects.scope-data', $project));

        $response->assertOk()
            ->assertJsonCount(1, 'areas')
            ->assertJsonFragment(['name' => $member->fullName()]);
    }

    public function test_quick_client_creation(): void
    {
        $manager = $this->makeUser(RoleName::Manager);

        $response = $this->actingAs($manager)->postJson(route('clients.quick'), ['name' => 'Rápido SRL']);

        $response->assertCreated()->assertJsonFragment(['name' => 'Rápido SRL']);
        $this->assertDatabaseHas('clients', ['name' => 'Rápido SRL']);
    }
}
