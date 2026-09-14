<?php

namespace App\Support\Enums;

enum EmployeeSpecialty: string
{
    case Backend = 'backend';
    case Frontend = 'frontend';
    case FullStack = 'fullstack';
    case UxUi = 'ux_ui';
    case GraphicDesign = 'graphic_design';
    case Marketing = 'marketing';
    case Seo = 'seo';
    case Advertising = 'advertising';
    case ProjectManager = 'project_manager';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Backend => 'Backend',
            self::Frontend => 'Frontend',
            self::FullStack => 'Full Stack',
            self::UxUi => 'UX/UI',
            self::GraphicDesign => 'Diseño gráfico',
            self::Marketing => 'Marketing',
            self::Seo => 'SEO',
            self::Advertising => 'Publicidad',
            self::ProjectManager => 'Project Manager',
            self::Other => 'Otro',
        };
    }
}
