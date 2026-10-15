<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Support\CompanySignature;
use App\Support\SignatureCleaner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LimpiarFirmas extends Command
{
    protected $signature = 'firmas:limpiar';

    protected $description = 'Quita el fondo de las firmas ya guardadas (empresa y colaboradores) y conserva los originales en firmas/_originales';

    public function handle(): int
    {
        $disk = Storage::disk('local');

        $empresa = CompanySignature::path();

        if ($empresa !== null) {
            $this->respaldar($empresa);
            $disk->put('firmas/gerente-general.png', SignatureCleaner::clean($disk->get($empresa)));

            if ($empresa !== 'firmas/gerente-general.png') {
                $disk->delete($empresa);
            }

            $this->info('Firma de la empresa limpiada.');
        }

        foreach (Employee::whereNotNull('firma_path')->get() as $empleado) {
            if (! $disk->exists($empleado->firma_path)) {
                $this->warn("Sin archivo: {$empleado->fullName()}");

                continue;
            }

            $this->respaldar($empleado->firma_path);

            $nuevo = 'firmas/colaboradores/'.Str::uuid().'.png';
            $disk->put($nuevo, SignatureCleaner::clean($disk->get($empleado->firma_path)));
            $disk->delete($empleado->firma_path);
            $empleado->update(['firma_path' => $nuevo]);

            $this->info("Firma limpiada: {$empleado->fullName()}");
        }

        return self::SUCCESS;
    }

    private function respaldar(string $ruta): void
    {
        Storage::disk('local')->copy($ruta, 'firmas/_originales/'.Str::random(6).'-'.basename($ruta));
    }
}
