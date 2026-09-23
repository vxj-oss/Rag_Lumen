<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['nombre' => 'Diseño', 'descripcion' => 'Diseño gráfico y piezas visuales.'],
            ['nombre' => 'Contenido', 'descripcion' => 'Redacción y estrategia de contenidos.'],
            ['nombre' => 'Redes sociales', 'descripcion' => 'Gestión y pauta en redes sociales.'],
            ['nombre' => 'SEO/SEM', 'descripcion' => 'Posicionamiento orgánico y publicidad en buscadores.'],
            ['nombre' => 'Audiovisual', 'descripcion' => 'Producción de video, foto y motion graphics.'],
            ['nombre' => 'Estrategia', 'descripcion' => 'Planificación, cuentas y dirección de proyectos.'],
        ];

        foreach ($areas as $area) {
            Area::firstOrCreate(['nombre' => $area['nombre']], $area + ['activa' => true]);
        }
    }
}
