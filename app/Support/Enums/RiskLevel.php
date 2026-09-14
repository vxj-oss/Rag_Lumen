<?php

namespace App\Support\Enums;

enum RiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public static function fromScore(int $score): self
    {
        $thresholds = config('risk.levels');

        return match (true) {
            $score >= $thresholds['critical'] => self::Critical,
            $score >= $thresholds['high'] => self::High,
            $score >= $thresholds['medium'] => self::Medium,
            default => self::Low,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Bajo',
            self::Medium => 'Medio',
            self::High => 'Alto',
            self::Critical => 'Crítico',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'green',
            self::Medium => 'yellow',
            self::High => 'red',
            self::Critical => 'red',
        };
    }
}
