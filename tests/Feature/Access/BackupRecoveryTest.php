<?php

namespace Tests\Feature\Access;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_encrypted_backup_restores_database_and_private_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create(['name' => 'Nombre original']);
        Storage::disk('local')->put('evidencias/privado.txt', 'Evidencia original');
        $service = app(BackupService::class);
        $name = $service->create();
        $this->assertStringNotContainsString('Evidencia original', Storage::disk('local')->get($name));
        $user->update(['name' => 'Nombre modificado']);
        Storage::disk('local')->put('evidencias/privado.txt', 'Alterado');
        $service->restore($name);
        $this->assertSame('Nombre original', $user->fresh()->name);
        $this->assertSame('Evidencia original', Storage::disk('local')->get('evidencias/privado.txt'));
        $this->assertSame(2, $user->fresh()->auth_version);
    }

    public function test_backup_paths_cannot_escape_private_backup_directory(): void
    {
        $this->expectException(\RuntimeException::class);
        app(BackupService::class)->read('../.env');
    }

    public function test_restore_command_cannot_replace_live_data_without_maintenance_and_confirmation(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        User::factory()->create();
        $name = app(BackupService::class)->create();
        $this->artisan('workspace:restore', ['backup' => $name])->assertFailed();
        $this->assertSame(1, User::count());
        $this->artisan('workspace:restore', ['backup' => $name, '--verify-only' => true])->assertSuccessful();
    }
}
