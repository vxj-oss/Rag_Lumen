<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable(['area', 'nombre', 'contenido', 'entregables', 'version'])]
class TerminosReferencia extends Model
{
    protected $table = 'terminos_referencia';

    public const AREAS = [
        'software' => 'Software',
        'web' => 'Web',
        'marketing' => 'Marketing',
    ];

    protected function casts(): array
    {
        return [
            'entregables' => 'array',
        ];
    }

    public static function porArea(string $area): ?self
    {
        return self::where('area', $area)->first();
    }

    public function areaLabel(): string
    {
        return self::AREAS[$this->area] ?? (string) $this->area;
    }

    /** Faltas vigentes que aplican a este TDR: las generales y las de su área. */
    public function faltasAplicables(): Collection
    {
        return Falta::where('activa', true)
            ->where(fn ($q) => $q->whereNull('area')->orWhere('area', $this->area))
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Divide el contenido en secciones: bloques separados por una línea en blanco,
     * donde la primera línea es el título (p. ej. "1. OBJETO") y el resto el cuerpo.
     * Las líneas que empiezan con "- " son viñetas.
     *
     * @return array<int, array{numero: ?string, titulo: string, lineas: array<int, array{tipo: string, texto: string}>}>
     */
    public function secciones(): array
    {
        $secciones = [];

        foreach (preg_split('/\R{2,}/', trim((string) $this->contenido)) as $bloque) {
            $lineas = array_values(array_filter(array_map('trim', preg_split('/\R/', $bloque)), fn ($l) => $l !== ''));

            if ($lineas === []) {
                continue;
            }

            $titulo = array_shift($lineas);
            $numero = null;

            if (preg_match('/^(\d+)[.)]\s*(.+)$/u', $titulo, $m)) {
                $numero = $m[1];
                $titulo = $m[2];
            }

            $secciones[] = [
                'numero' => $numero,
                'titulo' => $titulo,
                'lineas' => array_map(
                    fn (string $l) => str_starts_with($l, '- ')
                        ? ['tipo' => 'li', 'texto' => substr($l, 2)]
                        : ['tipo' => 'p', 'texto' => $l],
                    $lineas
                ),
            ];
        }

        return $secciones;
    }

    /** Cualquier cambio del TDR o de sus faltas invalida las aceptaciones anteriores. */
    public function nuevaVersion(): void
    {
        $this->increment('version');
    }

    /** Sube la versión de los TDR a los que aplica una falta (null = todos). */
    public static function nuevaVersionPara(?string $area): void
    {
        self::query()->when($area !== null, fn ($q) => $q->where('area', $area))->increment('version');
    }
}
