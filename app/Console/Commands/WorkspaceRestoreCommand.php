<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class WorkspaceRestoreCommand extends Command
{
    protected $signature = 'workspace:restore {backup} {--verify-only : Comprueba sin restaurar} {--force : Confirma la restauración en mantenimiento}';
    protected $description = 'Restaura un respaldo privado de la misma versión; requiere mantenimiento';
    public function handle(BackupService $service): int
    {
        try {
            $name = $this->argument('backup');
            $service->read($name);
            if ($this->option('verify-only')) { $this->info('Respaldo íntegro, descifrable y compatible. No se modificaron datos.'); return self::SUCCESS; }
            if (! app()->isDownForMaintenance() || ! $this->option('force')) { $this->error('Activa mantenimiento (artisan down) y confirma con --force. Se reemplazarán los datos actuales.'); return self::FAILURE; }
            $safety = $service->create();
            $this->info('Respaldo de seguridad previo: '.$safety);
            $service->restore($name);
            $this->info('Restaurado. Revisa el sistema antes de ejecutar artisan up.'); return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
