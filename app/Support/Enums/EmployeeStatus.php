<?php

namespace App\Support\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case OnLeave = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
            self::OnLeave => 'De licencia',
        };
    }
}
