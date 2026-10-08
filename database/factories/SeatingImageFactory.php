<?php

namespace Database\Factories;

use App\Models\SeatingArea;
use App\Models\SeatingImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatingImage>
 */
class SeatingImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seating_area_id' => SeatingArea::factory(),
            'image_url' => 'images/seating/indoor.svg',
            'alt_text' => fake()->sentence(3),
            'tags' => [],
            'sort_order' => 0,
        ];
    }
}
