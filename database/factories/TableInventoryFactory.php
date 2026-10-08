<?php

namespace Database\Factories;

use App\Models\SeatingArea;
use App\Models\TableInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TableInventory>
 */
class TableInventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seating_area_id' => SeatingArea::factory(),
            'reservation_date' => now()->addDays(3)->toDateString(),
            'time_slot' => '11:00',
            'total_tables' => 2,
            'reserved_tables' => 0,
        ];
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes) => ['reserved_tables' => $attributes['total_tables']]);
    }
}
