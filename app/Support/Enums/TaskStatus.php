<?php

namespace App\Support\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En progreso',
            self::Review => 'En revisión',
            self::Blocked => 'Bloqueada',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'blue',
            self::Review => 'indigo',
            self::Blocked => 'red',
            self::Completed => 'green',
            self::Cancelled => 'gray',
        };
    }
}
