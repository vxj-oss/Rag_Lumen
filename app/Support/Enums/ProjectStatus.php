<?php

namespace App\Support\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Blocked = 'blocked';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planificación',
            self::InProgress => 'En progreso',
            self::Review => 'En revisión',
            self::Blocked => 'Bloqueado',
            self::Paused => 'Pausado',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planning => 'gray',
            self::InProgress => 'blue',
            self::Review => 'indigo',
            self::Blocked => 'red',
            self::Paused => 'yellow',
            self::Completed => 'green',
            self::Cancelled => 'gray',
        };
    }
}
