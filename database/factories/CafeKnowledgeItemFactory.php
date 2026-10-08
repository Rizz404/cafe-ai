<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\CafeKnowledgeItem;
use App\Modules\Knowledge\Enums\KnowledgeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CafeKnowledgeItem>
 */
class CafeKnowledgeItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cafe_id' => Cafe::factory(),
            'category' => KnowledgeCategory::General->value,
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'tags' => [],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inCategory(KnowledgeCategory $category): static
    {
        return $this->state(fn () => ['category' => $category->value]);
    }
}
