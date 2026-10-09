<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\SeatingArea;
use App\Models\TableInventory;
use App\Modules\Reservation\Support\TableAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class TableInventorySeeder extends Seeder
{
    /**
     * Every sitting is two hours, so these are the slots the demo cafe sells.
     *
     * @var list<string>
     */
    private const SLOTS = ['09:00', '11:00', '13:00', '15:00', '17:00', '19:00'];

    /**
     * Tables per seating area, keyed by the area's slug.
     *
     * @var array<string, int>
     */
    private const TOTAL_TABLES = [
        'indoor-lounge' => 6,
        'garden-terrace' => 5,
        'bar-counter' => 4,
        'private-room' => 1,
    ];

    public function run(): void
    {
        $cafe = Cafe::where('name', CafeSeeder::NAME)->firstOrFail();

        foreach (self::TOTAL_TABLES as $areaSlug => $totalTables) {
            $area = $cafe->seatingAreas()->where('slug', $areaSlug)->firstOrFail();

            $this->seedInventory($area, $totalTables);
        }
    }

    /**
     * Opens every slot for the booking window, with a deterministic spread of
     * already-reserved tables so some slots read as busy or full.
     */
    private function seedInventory(SeatingArea $area, int $totalTables): void
    {
        $today = CarbonImmutable::now($area->cafe->timezone)->startOfDay();
        $rows = [];

        for ($day = 0; $day <= TableAvailability::MAX_ADVANCE_DAYS; $day++) {
            $date = $today->addDays($day)->toDateString();

            foreach (self::SLOTS as $slot) {
                $roll = crc32($area->slug.$date.$slot) % 100;

                $rows[] = [
                    'seating_area_id' => $area->id,
                    'reservation_date' => $date,
                    'time_slot' => $slot,
                    'total_tables' => $totalTables,
                    'reserved_tables' => (int) floor((($roll / 100) ** 2) * ($totalTables + 0.99)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            TableInventory::upsert($chunk, ['seating_area_id', 'reservation_date', 'time_slot'], ['total_tables']);
        }
    }
}
