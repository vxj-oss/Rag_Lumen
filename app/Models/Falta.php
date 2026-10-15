<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['codigo', 'titulo', 'descripcion', 'gravedad', 'area', 'sancion', 'monto', 'activa'])]
class Falta extends Model
{
    protected $table = 'faltas';

    public const GRAVEDADES = [
        'leve' => 'Leve',
        'grave' => 'Grave',
        'muy_grave' => 'Muy grave',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'activa' => 'boolean',
        ];
    }

    public function areaLabel(): string
    {
        return $this->area === null ? 'General' : (TerminosReferencia::AREAS[$this->area] ?? $this->area);
    }

    public function gravedadLabel(): string
    {
        return self::GRAVEDADES[$this->gravedad] ?? $this->gravedad;
    }

    public function gravedadColor(): string
    {
        return match ($this->gravedad) {
            'muy_grave' => 'red',
            'grave' => 'yellow',
            default => 'gray',
        };
    }

    /** Texto que se imprime en el acta de incumplimiento como cláusula infringida. */
    public function referencia(): string
    {
        return "{$this->codigo} - {$this->titulo}";
    }
}
