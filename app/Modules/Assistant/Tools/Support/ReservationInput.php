<?php

namespace App\Modules\Assistant\Tools\Support;

use App\Models\Cafe;
use App\Modules\Reservation\Support\TableAvailability;
use Carbon\CarbonImmutable;

/**
 * Checks the date and time slot the model passes to the reservation tools, and
 * words the problem so the model can ask the guest for something that works.
 */
class ReservationInput
{
    public function __construct(private readonly TableAvailability $availability) {}

    public function parseDate(Cafe $cafe, string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value, $cafe->timezone)?->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * A reason the date cannot be booked, or null when it is fine.
     */
    public function dateProblem(Cafe $cafe, CarbonImmutable $date): ?string
    {
        $today = CarbonImmutable::now($cafe->timezone)->startOfDay();

        if ($date->lessThan($today)) {
            return 'That date is in the past. Ask the guest for a date from today onwards.';
        }

        if ($today->diffInDays($date) > TableAvailability::MAX_ADVANCE_DAYS) {
            return 'Tables can only be requested up to '.TableAvailability::MAX_ADVANCE_DAYS.' days ahead. Offer an earlier date, or call request_human_handover for something further out.';
        }

        return null;
    }

    public function isKnownSlot(Cafe $cafe, string $slot): bool
    {
        return in_array($slot, $this->availability->slotsFor($cafe), true);
    }

    public function unknownSlotMessage(Cafe $cafe): string
    {
        return 'time_slot must be one of the reservation slots: '.implode(', ', $this->availability->slotsFor($cafe)).'.';
    }
}
