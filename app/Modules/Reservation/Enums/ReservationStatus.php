<?php

namespace App\Modules\Reservation\Enums;

/**
 * Where a reservation request stands: the cafe confirms or cancels it.
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
