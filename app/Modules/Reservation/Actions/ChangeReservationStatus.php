<?php

namespace App\Modules\Reservation\Actions;

use App\Models\Reservation;
use App\Models\TableInventory;

/**
 * Confirms or cancels a reservation request. Cancelling gives the held table
 * back to the inventory, once.
 */
class ChangeReservationStatus
{
    public function handle(Reservation $reservation, string $status): Reservation
    {
        if ($status === Reservation::STATUS_CANCELLED && $reservation->status !== Reservation::STATUS_CANCELLED) {
            $this->releaseTable($reservation);
        }

        $reservation->update(['status' => $status]);

        return $reservation;
    }

    /**
     * Gives the table held by a reservation back to the inventory.
     */
    public function releaseTable(Reservation $reservation): void
    {
        TableInventory::where('seating_area_id', $reservation->seating_area_id)
            ->whereDate('reservation_date', $reservation->reservation_date->toDateString())
            ->where('time_slot', $reservation->time_slot)
            ->decrement('reserved_tables');
    }
}
