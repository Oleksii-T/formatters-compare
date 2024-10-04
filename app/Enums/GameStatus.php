<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum GameStatus:int
{
    use EnumTrait;

    case DRAFT = 1;
    case PUBLISHED = 2;
    case CALENDAR_PUBLISHED = 3;
    case CALENDAR_DRAFT = 4;

    public static function getReadable($val)
    {
        return match ($val) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::CALENDAR_DRAFT => 'Calendar Draft',
            self::CALENDAR_PUBLISHED => 'Calendar Published',
        };
    }

}
