<?php

namespace App\Modules\Reservation\Support;

use App\Models\Cafe;
use App\Models\SeatingArea;
use App\Models\TableInventory;
use Carbon\CarbonImmutable;

/**
 * The single place where table availability and slot quotes are computed.
 * Both the AI Barista tools and the guest-facing reservation wizard go
 * through here, so they can never disagree.
 */
class TableAvailability
{
    /**
     * How far ahead a table can be requested.
     */
    public const MAX_ADVANCE_DAYS = 60;

    /**
     * Every slot is a fixed two-hour sitting.
     */
    public const SLOT_MINUTES = 120;

    /**
     * Whether the party size suits the seating area.
     */
    public function fitsParty(SeatingArea $area, int $guests): bool
    {
        return $guests >= max(1, $area->min_guests) && $guests <= $area->max_guests;
    }

    /**
     * The slot start times the cafe takes reservations for, earliest first.
     *
     * @return list<string>
     */
    public function slotsFor(Cafe $cafe): array
    {
        return TableInventory::query()
            ->whereIn('seating_area_id', $cafe->seatingAreas()->where('is_active', true)->select('id'))
            ->distinct()
            ->orderBy('time_slot')
            ->pluck('time_slot')
            ->all();
    }

    /**
     * "11:00–13:00" for a slot that starts at 11:00.
     */
    public static function slotRange(string $slot): string
    {
        $start = CarbonImmutable::createFromFormat('H:i', $slot);

        return $start->format('H:i').'–'.$start->addMinutes(self::SLOT_MINUTES)->format('H:i');
    }

    /**
     * Whether the slot has already begun in the cafe's own time zone.
     */
    public function isSlotInPast(Cafe $cafe, CarbonImmutable $date, string $slot): bool
    {
        $start = CarbonImmutable::parse($date->toDateString().' '.$slot, $cafe->timezone);

        return $start->lessThanOrEqualTo(CarbonImmutable::now($cafe->timezone));
    }

    /**
     * Checks one slot. Returns null if there is no free table in it, which is
     * the one place availability truth comes from, never the model.
     *
     * @return array{date: string, time_slot: string, time_range: string, available_tables: int, fee: float, minimum_spend: ?float}|null
     */
    public function quote(SeatingArea $area, CarbonImmutable $date, string $slot, bool $lockForUpdate = false): ?array
    {
        $query = TableInventory::where('seating_area_id', $area->id)
            ->whereDate('reservation_date', $date->toDateString())
            ->where('time_slot', $slot);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $inventory = $query->first();

        if (! $inventory || ! $inventory->isAvailable()) {
            return null;
        }

        return [
            'date' => $date->toDateString(),
            'time_slot' => $slot,
            'time_range' => self::slotRange($slot),
            'available_tables' => $inventory->availableTables(),
            'fee' => (float) $area->reservation_fee,
            'minimum_spend' => $area->minimum_spend !== null ? (float) $area->minimum_spend : null,
        ];
    }

    /**
     * Every slot of the day that still has a free table and has not started.
     *
     * @return list<string>
     */
    public function availableSlots(SeatingArea $area, CarbonImmutable $date): array
    {
        $cafe = $area->cafe;

        return TableInventory::where('seating_area_id', $area->id)
            ->whereDate('reservation_date', $date->toDateString())
            ->orderBy('time_slot')
            ->get()
            ->filter(fn (TableInventory $row) => $row->isAvailable() && ! $this->isSlotInPast($cafe, $date, $row->time_slot))
            ->pluck('time_slot')
            ->values()
            ->all();
    }
}
