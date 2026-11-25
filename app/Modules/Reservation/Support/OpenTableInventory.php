<?php

namespace App\Modules\Reservation\Support;

use App\Models\SeatingArea;
use App\Models\TableInventory;
use Carbon\CarbonImmutable;

/**
 * Keeps the booking window open: adds the missing days up to
 * TableAvailability::MAX_ADVANCE_DAYS for one seating area, repeating the
 * slots and table counts of the area's latest inventory day. Existing rows,
 * and so every reserved table, are never changed.
 */
final class OpenTableInventory
{
    /**
     * @return int The number of slots added.
     */
    public function handle(SeatingArea $area): int
    {
        $latestDate = TableInventory::query()->where('seating_area_id', $area->id)->max('reservation_date');

        if ($latestDate === null) {
            return 0;
        }

        $template = TableInventory::query()
            ->where('seating_area_id', $area->id)
            ->whereDate('reservation_date', CarbonImmutable::parse($latestDate)->toDateString())
            ->orderBy('time_slot')
            ->get(['time_slot', 'total_tables']);

        $today = CarbonImmutable::now($area->cafe->timezone)->startOfDay();
        $horizon = $today->addDays(TableAvailability::MAX_ADVANCE_DAYS);
        $from = CarbonImmutable::parse($latestDate)->addDay();
        $from = $from->lessThan($today) ? $today : $from;
        $rows = [];

        for ($date = $from; $date->lessThanOrEqualTo($horizon); $date = $date->addDay()) {
            foreach ($template as $slot) {
                $rows[] = [
                    'seating_area_id' => $area->id,
                    'reservation_date' => $date->toDateString(),
                    'time_slot' => $slot->time_slot,
                    'total_tables' => $slot->total_tables,
                    'reserved_tables' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $inserted = 0;
        foreach (array_chunk($rows, 200) as $chunk) {
            $inserted += TableInventory::query()->insertOrIgnore($chunk);
        }

        return $inserted;
    }
}
