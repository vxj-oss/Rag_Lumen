<?php

namespace App\Support\Enums;

enum ProjectType: string
{
    case SoftwareDevelopment = 'software_development';
    case WebDevelopment = 'web_development';
    case UxUiDesign = 'ux_ui_design';
    case GraphicDesign = 'graphic_design';
    case DigitalMarketing = 'digital_marketing';
    case Seo = 'seo';
    case Advertising = 'advertising';
    case Branding = 'branding';
    case Campaign = 'campaign';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SoftwareDevelopment => 'Desarrollo de software',
            self::WebDevelopment => 'Desarrollo web',
            self::UxUiDesign => 'Diseño UX/UI',
            self::GraphicDesign => 'Diseño gráfico',
            self::DigitalMarketing => 'Marketing digital',
            self::Seo => 'SEO',
            self::Advertising => 'Publicidad',
            self::Branding => 'Branding',
            self::Campaign => 'Campaña',
            self::Other => 'Otro',
        };
    }
}
