<?php

namespace App\Modules\Seating\Enums;

/**
 * Where in the cafe a seating area is.
 */
enum AreaType: string
{
    case Indoor = 'indoor';
    case Outdoor = 'outdoor';
    case Bar = 'bar';
    case Private = 'private';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
