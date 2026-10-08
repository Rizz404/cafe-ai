<?php

namespace App\Models;

use App\Modules\Menu\Enums\MenuCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One drink or dish on the cafe's menu. The price here is the only price the
 * AI Barista may ever quote, and only after reading it through a tool call.
 */
#[Fillable([
    'cafe_id',
    'category',
    'name',
    'slug',
    'description',
    'translations',
    'price',
    'image_url',
    'tags',
    'allergens',
    'serving',
    'calories',
    'is_featured',
    'is_sold_out',
    'is_active',
    'sort_order',
])]
class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORY_COFFEE = MenuCategory::Coffee->value;

    public const CATEGORY_NON_COFFEE = MenuCategory::NonCoffee->value;

    public const CATEGORY_TEA = MenuCategory::Tea->value;

    public const CATEGORY_FOOD = MenuCategory::Food->value;

    public const CATEGORY_SNACK = MenuCategory::Snack->value;

    public const CATEGORY_DESSERT = MenuCategory::Dessert->value;

    /**
     * Tags a guest can filter on when they have a dietary need.
     */
    public const DIETARY_TAGS = ['vegan', 'vegetarian', 'gluten_free', 'dairy_free', 'halal'];

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return MenuCategory::values();
    }

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'tags' => 'array',
            'allergens' => 'array',
            'price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_sold_out' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    /**
     * The picture to show: a full URL as stored, or a file under public/.
     */
    public function getImageSourceAttribute(): ?string
    {
        if (blank($this->image_url)) {
            return null;
        }

        return str_starts_with($this->image_url, 'http') ? $this->image_url : asset($this->image_url);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }

    public function translatedName(string $locale): string
    {
        $value = $this->translations[$locale]['name'] ?? null;

        return filled($value) ? $value : $this->name;
    }

    public function translatedDescription(string $locale): ?string
    {
        $value = $this->translations[$locale]['description'] ?? null;

        return filled($value) ? $value : $this->description;
    }
}
