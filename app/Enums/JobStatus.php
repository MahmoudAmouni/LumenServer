<?php

namespace App\Enums;

enum JobStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case DRAFT = 'draft';
    case PAUSED = 'paused';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

