<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WorkspaceBackupCommand extends Command
{
    protected $signature = 'workspace:backup {--verify : Verifica que el respaldo se puede leer y descifrar}';
    protected $description = 'Respalda base de datos y archivos locales con cifrado y verificación';
    public function handle(BackupService $service): int
    {
        try {
            $name = $service->create();
            if ($this->option('verify')) $service->read($name);
            Cache::forever('workspace-last-backup', ['at' => now()->toIso8601String(), 'file' => $name, 'verified' => (bool) $this->option('verify')]);
            $this->info($name); return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
