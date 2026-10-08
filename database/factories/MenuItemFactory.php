<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Modules\Menu\Enums\MenuCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'cafe_id' => Cafe::factory(),
            'category' => fake()->randomElement(MenuCategory::values()),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(15, 90) * 1000,
            'tags' => [],
            'allergens' => [],
            'is_featured' => false,
            'is_sold_out' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['is_sold_out' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
