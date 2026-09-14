<?php

namespace App\Support\Enums;

enum RagSourceType: string
{
    case Pdf = 'pdf';
    case Docx = 'docx';
    case Txt = 'txt';
    case Markdown = 'markdown';
    case Csv = 'csv';
    case Manual = 'manual';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Docx => 'Word (DOCX)',
            self::Txt => 'Texto plano',
            self::Markdown => 'Markdown',
            self::Csv => 'CSV',
            self::Manual => 'Ingresado manualmente',
            self::Other => 'Otro',
        };
    }
}
