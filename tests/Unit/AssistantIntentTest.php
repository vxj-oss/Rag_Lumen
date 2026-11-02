<?php

namespace Tests\Unit;

use App\Services\Assistant\AssistantIntent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AssistantIntentTest extends TestCase
{
    #[DataProvider('peticiones')]
    public function test_detects_creation_requests_even_with_typos(string $pregunta, ?string $tipo, string $nombre): void
    {
        $this->assertSame($tipo, AssistantIntent::creation($pregunta));

        if ($tipo !== null) {
            $this->assertSame($nombre, AssistantIntent::name($pregunta, $tipo));
        }
    }

    public static function peticiones(): array
    {
        return [
            'proyecto con error de tipeo' => ['crea un poryecto con el nombre de techstore', 'project', 'techstore'],
            'proyecto entre comillas' => ['crea un proyecto llamado "Tech Store" para el cliente X', 'project', 'Tech Store'],
            'proyecto nuevo' => ['agrega nuevo proyecto Web Corporativa', 'project', 'Web Corporativa'],
            'actividad con error de tipeo' => ['creame una activdad Diseño de logo en el proyecto Andina', 'activity', 'Diseño de logo'],
            'actividad con nombre' => ['crea una actividad con el nombre de Landing page', 'activity', 'Landing page'],
            'consulta, no creación' => ['cuantos proyectos tengo', null, ''],
            'proyectos nuevos (consulta)' => ['que proyectos nuevos hay', null, ''],
            'eliminar no es crear' => ['elimina el proyecto andina', null, ''],
            'ver no es crear' => ['quiero ver el proyecto techstore', null, ''],
        ];
    }
}
