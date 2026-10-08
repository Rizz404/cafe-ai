<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\SeatingArea;
use App\Modules\Seating\Enums\AreaType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SeatingArea>
 */
class SeatingAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'cafe_id' => Cafe::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'area_type' => AreaType::Indoor->value,
            'min_guests' => 1,
            'max_guests' => 4,
            'features' => [],
            'reservation_fee' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function forGuests(int $min, int $max): static
    {
        return $this->state(fn () => ['min_guests' => $min, 'max_guests' => $max]);
    }

    public function withFee(float $fee): static
    {
        return $this->state(fn () => ['reservation_fee' => $fee]);
    }
}
