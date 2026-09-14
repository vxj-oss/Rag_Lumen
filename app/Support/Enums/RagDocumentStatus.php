<?php

namespace App\Support\Enums;

enum RagDocumentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processing => 'Procesando',
            self::Processed => 'Procesado',
            self::Failed => 'Falló',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'blue',
            self::Processed => 'green',
            self::Failed => 'red',
        };
    }
}
