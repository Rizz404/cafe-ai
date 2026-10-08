<?php

namespace App\Modules\Reservation\Queries;

use App\Models\Cafe;
use App\Models\SeatingArea;
use App\Modules\Reservation\Support\TableAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * No table is free in the requested area and slot: find the other areas that
 * can seat the same party at the same time, and the other slots of the same
 * area that day.
 */
class FindAlternatives
{
    public function __construct(private readonly TableAvailability $availability) {}

    /**
     * @return Collection<int, array{type: string, slug?: string, time_slot?: string, name: string, fee?: float}>
     */
    public function handle(Cafe $cafe, SeatingArea $requested, CarbonImmutable $date, string $timeSlot, int $guests, string $locale): Collection
    {
        $otherAreas = $cafe->seatingAreas()
            ->where('is_active', true)
            ->whereKeyNot($requested->id)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (SeatingArea $area) => $this->availability->fitsParty($area, $guests))
            ->map(function (SeatingArea $area) use ($date, $timeSlot, $locale) {
                $quote = $this->availability->quote($area, $date, $timeSlot);

                return $quote ? [
                    'type' => 'area',
                    'slug' => $area->slug,
                    'name' => $area->translatedName($locale),
                    'fee' => $quote['fee'],
                ] : null;
            })
            ->filter()
            ->values();

        $otherSlots = collect($this->availability->availableSlots($requested, $date))
            ->reject(fn (string $slot) => $slot === $timeSlot)
            ->map(fn (string $slot) => [
                'type' => 'slot',
                'time_slot' => $slot,
                'name' => TableAvailability::slotRange($slot),
            ])
            ->values();

        return $otherSlots->merge($otherAreas)->values();
    }
}
