<?php

namespace App\Modules\Reservation\Enums;

/**
 * Occasions a guest can mention so the team can prepare the table.
 */
enum Occasion: string
{
    case Birthday = 'birthday';
    case Date = 'date';
    case Family = 'family';
    case Meeting = 'meeting';
    case Work = 'work';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
