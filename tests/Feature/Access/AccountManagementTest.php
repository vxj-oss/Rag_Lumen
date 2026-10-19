<?php

namespace Tests\Feature\Access;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
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

    public function test_only_admin_can_manage_accounts_and_system_status(): void
    {
        foreach (['employee', 'project_lead', 'manager'] as $role) {
            $this->actingAs($this->account($role))->get(route('users.index'))->assertForbidden();
            $this->get(route('system.index'))->assertForbidden();
            $this->post(route('users.store'), [])->assertForbidden();
        }
    }

    public function test_admin_creates_linked_account_with_hashed_permanent_password(): void
    {
        $admin = $this->account('administrator');
        $employee = Employee::factory()->create();
        $this->actingAs($admin)->post(route('users.store'), ['name' => 'Ana Torres', 'email' => 'ana@example.test', 'employee_id' => $employee->id, 'role' => 'project_lead', 'password' => 'TemporaryPass123!', 'password_confirmation' => 'TemporaryPass123!'])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));
        $user = User::where('email', 'ana@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('TemporaryPass123!', $user->password));
        $this->assertFalse($user->force_password_change);
        $this->assertTrue($user->hasRole('project_lead'));
        $this->assertSame($user->id, $employee->fresh()->usuario_id);
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_inactive_accounts_cannot_login(): void
    {
        $user = $this->account('employee');
        $user->update(['is_active' => false]);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_last_active_admin_cannot_be_deactivated_or_demoted(): void
    {
        $admin = $this->account('administrator');
        foreach ([['role' => 'employee', 'is_active' => 1], ['role' => 'administrator', 'is_active' => 0]] as $data) {
            $this->actingAs($admin)->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, ...$data])->assertSessionHasErrors('role');
        }
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_password_reset_requires_admin_password_and_invalidates_previous_sessions(): void
    {
        $admin = $this->account('administrator');
        $target = $this->account('employee');
        $payload = ['admin_password' => 'wrong', 'password' => 'ReplacementPass123!', 'password_confirmation' => 'ReplacementPass123!'];
        $this->actingAs($admin)->put(route('users.password', $target), $payload)->assertSessionHasErrors('admin_password');
        $payload['admin_password'] = 'password';
        $this->put(route('users.password', $target), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $target->fresh()->auth_version);
        $this->assertFalse($target->fresh()->force_password_change);
        $this->actingAs($target->fresh())->withSession(['auth_version' => 1])->getJson(route('workspace.search', ['q' => 'test']))->assertUnauthorized();
    }

    public function test_role_changes_revoke_sessions_and_cannot_create_invalid_roles(): void
    {
        $admin = $this->account('administrator');
        $target = $this->account('employee');
        $payload = ['name' => $target->name, 'email' => $target->email, 'role' => 'superuser', 'is_active' => 1];
        $this->actingAs($admin)->put(route('users.update', $target), $payload)->assertSessionHasErrors('role');
        $payload['role'] = 'manager';
        $this->put(route('users.update', $target), $payload)->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->isManager());
        $this->assertSame(2, $target->fresh()->auth_version);
    }

    public function test_new_login_after_reset_keeps_permanent_password_and_denies_worker_change(): void
    {
        $user = $this->account('employee');
        $user->update(['auth_version' => 3, 'force_password_change' => false]);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHas('auth_version', 3);
        $this->put(route('password.update'), ['current_password' => 'password', 'password' => 'MyNewPassword123!', 'password_confirmation' => 'MyNewPassword123!'])->assertForbidden();
        $this->assertFalse($user->fresh()->force_password_change);
        $this->assertSame(3, $user->fresh()->auth_version);
        $this->get(route('tasks.index'))->assertOk();
    }

    public function test_validation_does_not_flash_admin_password(): void
    {
        $admin = $this->account('administrator');
        $user = $this->account('employee');
        $this->actingAs($admin)->put(route('users.password', $user), ['admin_password' => 'private-secret', 'password' => 'bad', 'password_confirmation' => 'bad'])->assertSessionHasErrors();
        $this->assertArrayNotHasKey('admin_password',session()->getOldInput());
    }
}
