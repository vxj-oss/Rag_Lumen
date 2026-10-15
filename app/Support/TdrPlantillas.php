<?php

namespace App\Support;

class TdrPlantillas
{
    /** @return array<string, array{nombre: string, contenido: string, entregables: array<int, array{producto: string, plazo: string}>}> */
    public static function todas(): array
    {
        return [
            'software' => [
                'nombre' => 'Servicio especializado de desarrollo de software (backend y sistemas)',
                'contenido' => self::contenido(
                    objetivo: 'Contratar el servicio de un(a) desarrollador(a) de software que diseñe, desarrolle, pruebe y mantenga los módulos, APIs y bases de datos de los sistemas que LA EMPRESA construye para sus clientes, garantizando calidad, seguridad y continuidad operativa.',
                    alcances: [
                        'Diseñar y desarrollar módulos, servicios y APIs del lado servidor según los requerimientos de cada proyecto.',
                        'Modelar la base de datos, escribir migraciones y optimizar consultas.',
                        'Integrar servicios de terceros (pasarelas de pago, correo, almacenamiento, inteligencia artificial, entre otros).',
                        'Escribir y mantener pruebas automatizadas; corregir errores e incidencias reportadas.',
                        'Aplicar buenas prácticas de seguridad (validación de datos, control de accesos, protección de credenciales).',
                        'Mantener el código en el repositorio con control de versiones y participar en las revisiones de código.',
                        'Documentar el software desarrollado (instalación, configuración y uso).',
                        'Apoyar el despliegue y la puesta en producción de las versiones.',
                        'Otras actividades relacionadas con el objeto del servicio indicadas por el líder del proyecto.',
                    ],
                    formacion: 'Bachiller, titulado(a) o estudiante de los últimos ciclos de Ingeniería de Sistemas, Informática, Ciencias de la Computación o carreras afines.',
                    experiencia: 'Experiencia mínima de un (01) año en desarrollo de software backend, acreditada con portafolio, contratos, constancias o proyectos verificables.',
                    capacitacion: [
                        'Conocimiento de PHP/Laravel o framework equivalente, SQL (MySQL/MariaDB) y APIs REST.',
                        'Manejo de Git y flujos de trabajo con ramas y revisión de código.',
                        'Conocimientos de pruebas automatizadas y seguridad de aplicaciones web.',
                    ],
                ),
                'entregables' => self::entregablesMensuales('Informe de actividades con enlaces al repositorio, cambios integrados, evidencias de pruebas y despliegues'),
            ],
            'web' => [
                'nombre' => 'Servicio especializado de desarrollo y diseño web (frontend y experiencia de usuario)',
                'contenido' => self::contenido(
                    objetivo: 'Contratar el servicio de un(a) profesional de desarrollo y diseño web que construya interfaces responsivas, accesibles y de alto rendimiento, y que diseñe experiencias de usuario coherentes con la identidad de cada cliente.',
                    alcances: [
                        'Maquetar e implementar interfaces responsivas (escritorio, tablet y móvil) a partir de los diseños aprobados.',
                        'Desarrollar componentes reutilizables y accesibles, y conectarlos con las APIs del proyecto.',
                        'Diseñar flujos de usuario, wireframes y prototipos (UX/UI) y validarlos con el líder del proyecto.',
                        'Optimizar el rendimiento del sitio (tiempos de carga, imágenes, métricas web esenciales).',
                        'Garantizar la compatibilidad entre navegadores y dispositivos.',
                        'Aplicar SEO técnico básico en las páginas desarrolladas (estructura, metadatos, velocidad).',
                        'Corregir errores visuales y de usabilidad reportados.',
                        'Otras actividades relacionadas con el objeto del servicio indicadas por el líder del proyecto.',
                    ],
                    formacion: 'Bachiller, titulado(a) o estudiante de los últimos ciclos de Ingeniería de Sistemas, Informática, Diseño Gráfico, Diseño Web, Comunicación Digital o carreras afines.',
                    experiencia: 'Experiencia mínima de un (01) año en desarrollo frontend o diseño UX/UI, acreditada con portafolio, enlaces a proyectos publicados, contratos o constancias.',
                    capacitacion: [
                        'Dominio de HTML5, CSS3 y JavaScript; conocimiento de un framework de interfaces (Tailwind, Alpine, Vue o React).',
                        'Manejo de herramientas de diseño y prototipado (Figma o similar).',
                        'Conocimientos de accesibilidad, diseño responsivo y optimización de rendimiento.',
                    ],
                ),
                'entregables' => self::entregablesMensuales('Informe de actividades con capturas, enlaces a las versiones publicadas o de prueba y métricas de rendimiento'),
            ],
            'marketing' => [
                'nombre' => 'Servicio especializado de marketing digital, SEO, publicidad y diseño de marca',
                'contenido' => self::contenido(
                    objetivo: 'Contratar el servicio de un(a) profesional de marketing digital que planifique, ejecute y mida las estrategias de contenido, posicionamiento (SEO/SEM), publicidad y diseño de piezas gráficas que LA EMPRESA ofrece a sus clientes.',
                    alcances: [
                        'Elaborar estrategias, calendarios de contenido y propuestas de comunicación para cada cliente.',
                        'Gestionar campañas de publicidad digital (redes sociales y buscadores) y optimizar su inversión.',
                        'Realizar auditorías SEO, investigación de palabras clave, optimización on-page y reportes de posicionamiento.',
                        'Diseñar piezas gráficas, material audiovisual y elementos de identidad de marca aprobados por el cliente.',
                        'Analizar métricas (analítica web, redes sociales, campañas) y presentar informes con recomendaciones.',
                        'Canalizar toda propuesta económica o condición comercial a través del Gerente General, sin negociarla directamente con el cliente.',
                        'Otras actividades relacionadas con el objeto del servicio indicadas por el líder del proyecto.',
                    ],
                    formacion: 'Bachiller, titulado(a) o estudiante de los últimos ciclos de Marketing, Publicidad, Comunicaciones, Diseño Gráfico, Administración o carreras afines.',
                    experiencia: 'Experiencia mínima de un (01) año en marketing digital, SEO, publicidad o diseño gráfico, acreditada con portafolio, casos, contratos o constancias.',
                    capacitacion: [
                        'Manejo de plataformas de publicidad (Meta Ads, Google Ads) y de analítica (Google Analytics, Search Console).',
                        'Manejo de herramientas de diseño y edición (Canva, Adobe, Figma o similares).',
                        'Redacción de contenidos y presentación de informes de resultados.',
                    ],
                ),
                'entregables' => self::entregablesMensuales('Informe de actividades con métricas de campañas, piezas entregadas, calendario ejecutado y reporte de posicionamiento'),
            ],
        ];
    }

    /** @return array<int, array{producto: string, plazo: string}> */
    private static function entregablesMensuales(string $producto): array
    {
        $ordinales = ['Primer', 'Segundo', 'Tercer', 'Cuarto', 'Quinto', 'Sexto'];

        return collect($ordinales)->map(fn (string $ordinal, int $i) => [
            'producto' => "{$ordinal} entregable: {$producto}",
            'plazo' => 'Hasta el quinto (5.°) día hábil posterior a culminado el '.($i + 1).'.° mes de servicio.',
        ])->all();
    }

    /**
     * @param  array<int, string>  $alcances
     * @param  array<int, string>  $capacitacion
     */
    private static function contenido(string $objetivo, array $alcances, string $formacion, string $experiencia, array $capacitacion): string
    {
        $lista = fn (array $items) => implode("\n", array_map(fn ($i) => "- {$i}", $items));

        return implode("\n\n", [
            "1. FINALIDAD\nFortalecer la capacidad técnica de LA EMPRESA para ejecutar los proyectos de sus clientes con estándares de calidad, oportunidad y trazabilidad, mediante colaboradores especializados.",
            "2. OBJETIVO DE LA CONTRATACIÓN\n{$objetivo}",
            "3. ALCANCES DEL SERVICIO\nSon funciones principales de EL COLABORADOR:\n".$lista($alcances),
            "4. REQUISITOS MÍNIMOS DEL COLABORADOR\nFormación académica: {$formacion}\nExperiencia: {$experiencia}\nConocimientos y herramientas:\n".$lista($capacitacion)."\nOtros:\n".$lista([
                'Persona natural con RUC activo y habido, o disposición para emitir recibos por honorarios.',
                'Cuenta bancaria o Cuenta Interbancaria (CCI) a su nombre.',
                'Suscripción del Convenio de Colaboración Profesional y Confidencialidad.',
                'Proactividad, trabajo en equipo y habilidades blandas.',
            ]),
            "5. LUGAR Y PLAZO DE EJECUCIÓN\nLugar: modalidad remota, con coordinación a través de los canales y sistemas indicados por LA EMPRESA.\nPlazo: seis (06) meses renovables por acuerdo escrito, contados desde la fecha de suscripción del convenio.\nLas actividades se asignan por proyecto en el sistema; cada actividad se divide en tareas, y cada tarea se da por completada únicamente al presentar su evidencia.",
            "6. ENTREGABLES\nEL COLABORADOR presentará seis (06) informes mensuales (entregables) que describan las actividades ejecutadas, respaldados por las evidencias registradas en el sistema y por las actividades aprobadas por el líder del proyecto.",
            "7. CONFORMIDAD\nLa conformidad de cada entregable es otorgada por el líder del proyecto en un plazo máximo de siete (07) días calendario desde el día siguiente de recibido el entregable. Si existen observaciones, EL COLABORADOR las subsana en el plazo que se le indique y vuelve a presentar la evidencia.",
            "8. FORMA Y CONDICIONES DE PAGO\nLa compensación económica se establece en el anexo de cada proyecto, conforme al convenio.\nEl pago se realiza por entregable, previa presentación del informe de actividades y la conformidad del líder del proyecto.\nLA EMPRESA paga dentro de los diez (10) días hábiles siguientes a la conformidad, previa emisión del recibo por honorarios u otro comprobante que corresponda.",
            "9. CONFIDENCIALIDAD\nEL COLABORADOR guarda reserva absoluta sobre la información y documentación de LA EMPRESA y de sus clientes a la que tenga acceso, y queda prohibido revelarla a terceros.\nEL COLABORADOR cumple las políticas y estándares de seguridad de la información de LA EMPRESA, protege sus credenciales y no las comparte con terceros.\nEsta obligación permanece vigente después de concluido el servicio.",
            "10. PENALIDADES\nPenalidad por mora: en caso de retraso injustificado en la ejecución de las prestaciones, LA EMPRESA aplica una penalidad por cada día de atraso, calculada así:\nPenalidad diaria = 0.10 × monto / (F × plazo en días)\nDonde F = 0.40. El monto y el plazo corresponden al entregable que fue materia de retraso.\nLas penalidades se deducen del pago del entregable correspondiente.",
            "11. OTRAS PENALIDADES\nLas faltas y sanciones aplicables a EL COLABORADOR son las del Manual de Faltas y Sanciones que acompaña a este documento y forma parte integrante de él.",
            "12. RESOLUCIÓN DEL CONVENIO\nCualquiera de las partes puede resolver el convenio por incumplimiento de obligaciones, previa comunicación escrita. LA EMPRESA puede resolverlo de inmediato en los supuestos de incumplimiento grave señalados en el convenio y en el manual de faltas.",
            "13. CLÁUSULA ANTICORRUPCIÓN Y ANTISOBORNO\nEL COLABORADOR declara no haber ofrecido, prometido ni entregado beneficios indebidos a clientes, proveedores o personal de LA EMPRESA, y se obliga a mantener una conducta proba e íntegra durante la vigencia del servicio. Se obliga también a informar oportunamente cualquier acto de corrupción o inconducta del que tome conocimiento. El incumplimiento faculta a LA EMPRESA a resolver el convenio.",
            "14. SOLUCIÓN DE CONTROVERSIAS\nLas partes procuran resolver las controversias mediante diálogo directo y conciliación antes de acudir a la vía judicial.",
            "15. RESPONSABILIDAD POR VICIOS OCULTOS\nEL COLABORADOR responde por los defectos o vicios ocultos de los entregables por un plazo de treinta (30) días calendario contados desde la conformidad otorgada por el líder del proyecto.",
        ]);
    }
}
