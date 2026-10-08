<?php

namespace App\Modules\Assistant\Tools\Support;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\SeatingArea;

/**
 * The compact facts about a menu item or seating area that both the model and
 * the guest's cards are given.
 */
final class CatalogSummaries
{
    /**
     * @return array<string, mixed>
     */
    public static function menuItem(MenuItem $item, Cafe $cafe, string $locale): array
    {
        return [
            'menu_item_slug' => $item->slug,
            'name' => $item->translatedName($locale),
            'category' => $item->category,
            'price' => (float) $item->price,
            'currency' => $cafe->currency,
            'serving' => $item->serving,
            'tags' => $item->tags ?? [],
            'allergens' => $item->allergens ?? [],
            'is_sold_out' => $item->is_sold_out,
            'image_url' => $item->image_source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function seatingArea(SeatingArea $area, Cafe $cafe, string $locale): array
    {
        return [
            'name' => $area->translatedName($locale),
            'area_type' => $area->area_type,
            'min_guests' => $area->min_guests,
            'max_guests' => $area->max_guests,
            'features' => $area->features ?? [],
            'reservation_fee' => (float) $area->reservation_fee,
            'minimum_spend' => $area->minimum_spend !== null ? (float) $area->minimum_spend : null,
            'currency' => $cafe->currency,
            'thumbnail_url' => $area->images->first()?->image_source,
        ];
    }

    public static function menuItemBySlug(Cafe $cafe, string $slug): ?MenuItem
    {
        return $cafe->menuItems()->where('is_active', true)->where('slug', $slug)->first();
    }

    public static function seatingAreaBySlug(Cafe $cafe, string $slug): ?SeatingArea
    {
        return $cafe->seatingAreas()->where('is_active', true)->where('slug', $slug)->first();
    }
}
