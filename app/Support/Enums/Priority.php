<?php

namespace App\Support\Enums;

enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Critical => 'Crítica',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'blue',
            self::High => 'yellow',
            self::Critical => 'red',
        };
    }
}
