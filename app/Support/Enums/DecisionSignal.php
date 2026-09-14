<?php

namespace App\Support\Enums;

enum DecisionSignal: string
{
    case HighRisk = 'high_risk';
    case Delayed = 'delayed';
    case Blocked = 'blocked';
    case DeadlineRisk = 'deadline_risk';
    case ResourceOverload = 'resource_overload';
    case AttentionRequired = 'attention_required';
    case Healthy = 'healthy';

    public function label(): string
    {
        return match ($this) {
            self::HighRisk => 'Riesgo alto',
            self::Delayed => 'Atrasado',
            self::Blocked => 'Bloqueado',
            self::DeadlineRisk => 'Riesgo de fecha límite',
            self::ResourceOverload => 'Sobrecarga de recursos',
            self::AttentionRequired => 'Requiere atención',
            self::Healthy => 'Saludable',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HighRisk => 'red',
            self::Delayed => 'yellow',
            self::Blocked => 'red',
            self::DeadlineRisk => 'yellow',
            self::ResourceOverload => 'yellow',
            self::AttentionRequired => 'blue',
            self::Healthy => 'green',
        };
    }
}
