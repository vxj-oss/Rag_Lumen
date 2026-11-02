<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

/**
 * Detecta, con tolerancia a errores de tipeo, cuando el usuario pide crear un
 * proyecto o una actividad ("crea un poryecto llamado X"), y extrae el nombre.
 * Solo prepara un borrador: nada se guarda sin que el usuario lo confirme.
 */
class AssistantIntent
{
    private const VERBOS = ['crea', 'creame', 'crear', 'crees', 'agrega', 'agregame', 'agregar', 'anade', 'anadir', 'registra', 'registrar', 'genera', 'generar', 'nuevo', 'nueva'];

    private const SUSTANTIVOS = ['project' => 'proyecto', 'activity' => 'actividad'];

    /** @return 'project'|'activity'|null */
    public static function creation(string $question): ?string
    {
        $tokens = self::tokens($question);

        $hayVerbo = false;
        foreach ($tokens as $token) {
            if (self::parecido($token, self::VERBOS, 1)) {
                $hayVerbo = true;
                break;
            }
        }

        if (! $hayVerbo) {
            return null;
        }

        foreach ($tokens as $token) {
            foreach (self::SUSTANTIVOS as $tipo => $palabra) {
                if (self::parecido(rtrim($token, 's'), [$palabra], 2)) {
                    return $tipo;
                }
            }
        }

        return null;
    }

    /** Nombre pedido por el usuario: entre comillas, "llamado X", "con el nombre de X", "de nombre X"… */
    public static function name(string $question, string $type): string
    {
        if (preg_match('/["«“](.+?)["»”]/u', $question, $m)) {
            return self::limpiar($m[1]);
        }

        $corte = '\s+(?:con fecha|con prioridad|para el cliente|en el proyecto|para el proyecto|asignad[oa]|para ma[nñ]ana|para hoy|con tareas|tareas:)|[.;:]';
        $fin = '(?:'.$corte.'|$)';

        if (preg_match('/(?:con\s+(?:el\s+|un\s+)?nombre(?:\s+de|\s+como)?|de\s+nombre|llamad[oa]|titulad[oa]|que\s+se\s+llame|que\s+se\s+llama|nombrad[oa])\s+(.+?)'.$fin.'/iu', $question, $m)) {
            return self::limpiar($m[1]);
        }

        // Respaldo: lo que sigue a la palabra "proyecto"/"actividad" (aunque venga mal escrita).
        $palabra = self::SUSTANTIVOS[$type] ?? 'proyecto';
        foreach (preg_split('/\s+/u', trim($question)) as $i => $token) {
            $limpio = rtrim(Str::lower(Str::ascii(trim($token, " \t.,;:¿?¡!\"'«»"))), 's');
            if (self::parecido($limpio, [$palabra], 2)) {
                $resto = implode(' ', array_slice(preg_split('/\s+/u', trim($question)), $i + 1));
                $resto = preg_split('/'.$corte.'/iu', $resto)[0];

                return self::limpiar($resto);
            }
        }

        return '';
    }

    private static function limpiar(string $texto): string
    {
        $texto = trim($texto, " \t\n\r\0\x0B\"«»“”.,;:");

        // Quita muletillas iniciales: "de", "el", "la", "llamado"…
        $texto = preg_replace('/^(?:(?:de|del|el|la|un|una|llamad[oa]|nombre|con)\s+)+/iu', '', $texto);

        return trim($texto, " \"«»“”.,;:");
    }

    /** @return array<int, string> */
    private static function tokens(string $question): array
    {
        $normal = Str::lower(Str::ascii($question));

        return array_values(array_filter(preg_split('/[^a-z0-9]+/', $normal)));
    }

    /** ¿La palabra se parece (distancia de edición pequeña) a alguna de la lista? Solo palabras de 4+ letras. */
    private static function parecido(string $token, array $lista, int $distancia): bool
    {
        if (strlen($token) < 4) {
            return false;
        }

        foreach ($lista as $palabra) {
            if ($token === $palabra) {
                return true;
            }

            // Los verbos cortos (crea, haz) exigen coincidencia exacta; el resto tolera tipeo.
            if (strlen($palabra) >= 6 && abs(strlen($token) - strlen($palabra)) <= $distancia && levenshtein($token, $palabra) <= $distancia) {
                return true;
            }
        }

        return false;
    }
}
