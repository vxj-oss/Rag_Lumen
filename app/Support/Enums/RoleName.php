<?php

namespace App\Support\Enums;

enum RoleName: string
{
    case Administrator = 'administrator';
    case Manager = 'manager';
    case ProjectLead = 'project_lead';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Manager => 'Gerente',
            self::ProjectLead => 'Líder de Proyecto',
            self::Employee => 'Empleado',
        };
    }
}
