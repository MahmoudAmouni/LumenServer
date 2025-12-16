<?php

namespace App\Enums;

enum SkillType: int
{
    case REQUIRED = 1;
    case NICE_TO_HAVE = 2;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

