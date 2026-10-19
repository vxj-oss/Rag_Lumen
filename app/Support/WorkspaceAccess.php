<?php

namespace App\Support;

use App\Models\Area;
use App\Models\TerminosReferencia;
use App\Models\User;
use App\Support\Enums\EmployeeSpecialty;
use Illuminate\Support\Str;

class WorkspaceAccess
{
    /**
     * Áreas que el usuario puede abrir: administrador, gerente y líderes ven todas;
     * un colaborador, solo la de su especialidad.
     *
     * @return array<string, string> clave => etiqueta
     */
    public static function areasFor(User $user): array
    {
        $todas = TerminosReferencia::AREAS;

        if ($user->isAdmin() || $user->isManager()) {
            return $todas;
        }

        if ($user->isLeader()) {
            if (! $user->employee) {
                return [];
            }
            $managed = Area::whereHas('projects', fn ($q) => $q->where('empleado_responsable_id', $user->employee->id)->orWhere('areas_proyecto.lider_area_id', $user->employee->id))->orWhere('id', $user->employee->area_id)->with('employees:id,area_id,especialidad')->get();
            $names = $managed->pluck('nombre')->map(fn ($n) => Str::lower(Str::ascii($n)))
                ->merge($managed->flatMap(fn ($area) => $area->employees->map(fn ($e) => $e->especialidad?->tdrArea())))->filter()->all();
            if (array_intersect($names, ['diseno', 'contenido', 'redes sociales', 'seo/sem', 'audiovisual', 'estrategia'])) {
                $names[] = 'marketing';
            }
            $own = $user->employee?->especialidad?->tdrArea();

            return array_filter($todas, fn ($label, $key) => $key === $own || in_array($key, $names), ARRAY_FILTER_USE_BOTH);
        }

        $propia = $user->employee?->especialidad?->tdrArea();

        return $propia !== null ? [$propia => $todas[$propia]] : [];
    }

    /** @return array<int, string> valores de especialidad que pertenecen al área */
    public static function especialidades(string $area): array
    {
        return collect(EmployeeSpecialty::cases())
            ->filter(fn (EmployeeSpecialty $e) => $e->tdrArea() === $area)
            ->map(fn (EmployeeSpecialty $e) => $e->value)
            ->values()
            ->all();
    }

    public static function areaIdsFor(User $user): ?array
    {
        if ($user->isAdmin() || $user->isManager()) {
            return null;
        }
        $keys = array_keys(self::areasFor($user));
        if ($user->isEmployee() && $user->employee?->area_id) return [$user->employee->area_id];
        $led = $user->isLeader() && $user->employee ? Area::whereHas('projects', fn ($q) => $q->where('empleado_responsable_id', $user->employee->id)->orWhere('areas_proyecto.lider_area_id', $user->employee->id))->pluck('id')->all() : [];

        $explicit = array_values(array_unique(array_filter([...$led, $user->employee?->area_id])));
        if ($user->isLeader() && $explicit) return $explicit;
        return Area::all()->filter(fn ($area) => in_array(Str::lower(Str::ascii($area->nombre)), $keys))->pluck('id')->all();
    }

    /** @return array<string, string> */
    public static function tema(string $area): array
    {
        return match ($area) {
            'software' => [
                'badge' => 'blue',
                'tone' => 'blue',
                'dot' => 'bg-blue-500',
                'text' => 'text-blue-600',
                'soft' => 'bg-blue-50',
                'ring' => 'text-blue-500',
                'gradient' => 'from-blue-600 via-blue-500 to-sky-400',
                'tab' => 'bg-blue-600 text-white shadow-md shadow-blue-900/20',
                'border' => 'border-blue-200',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />',
            ],
            'web' => [
                'badge' => 'violet',
                'tone' => 'violet',
                'dot' => 'bg-violet-500',
                'text' => 'text-violet-600',
                'soft' => 'bg-violet-50',
                'ring' => 'text-violet-500',
                'gradient' => 'from-violet-600 via-violet-500 to-fuchsia-400',
                'tab' => 'bg-violet-600 text-white shadow-md shadow-violet-900/20',
                'border' => 'border-violet-200',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />',
            ],
            default => [
                'badge' => 'orange',
                'tone' => 'orange',
                'dot' => 'bg-orange-500',
                'text' => 'text-orange-600',
                'soft' => 'bg-orange-50',
                'ring' => 'text-orange-500',
                'gradient' => 'from-orange-500 via-amber-500 to-yellow-400',
                'tab' => 'bg-orange-500 text-white shadow-md shadow-orange-900/20',
                'border' => 'border-orange-200',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.34 3.94c1.5-.75 3.45-.75 4.95 0l5.2 2.6c.6.3.97.9.97 1.56v7.8c0 .66-.37 1.26-.97 1.56l-5.2 2.6c-1.5.75-3.45.75-4.95 0l-5.2-2.6A1.75 1.75 0 013.17 15.9V8.1c0-.66.37-1.26.97-1.56l5.2-2.6zM12 12l8-4M12 12v9M12 12L4 8" />',
            ],
        };
    }
}
