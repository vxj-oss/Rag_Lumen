<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Diseño', 'description' => 'Diseño gráfico y piezas visuales.'],
            ['name' => 'Contenido', 'description' => 'Redacción y estrategia de contenidos.'],
            ['name' => 'Redes sociales', 'description' => 'Gestión y pauta en redes sociales.'],
            ['name' => 'SEO/SEM', 'description' => 'Posicionamiento orgánico y publicidad en buscadores.'],
            ['name' => 'Audiovisual', 'description' => 'Producción de video, foto y motion graphics.'],
            ['name' => 'Estrategia', 'description' => 'Planificación, cuentas y dirección de proyectos.'],
        ];

        foreach ($areas as $area) {
            Area::firstOrCreate(['name' => $area['name']], $area + ['active' => true]);
        }
    }
}
