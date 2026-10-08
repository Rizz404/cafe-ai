<?php

namespace App\Modules\Menu\Queries;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Support\SearchText;
use Illuminate\Support\Collection;

/**
 * The active menu, narrowed by keyword, category, dietary need or budget.
 */
class SearchMenu
{
    /**
     * @return Collection<int, MenuItem>
     */
    public function handle(Cafe $cafe, string $query = '', ?string $category = null, ?string $dietary = null, ?float $maxPrice = null, int $limit = 6): Collection
    {
        $query = SearchText::normalize($query);

        return $cafe->menuItems()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (MenuItem $item) => ! $dietary || $item->hasTag($dietary))
            ->filter(fn (MenuItem $item) => $maxPrice === null || (float) $item->price <= $maxPrice)
            ->filter(fn (MenuItem $item) => SearchText::matches(implode(' ', [
                $item->name,
                $item->description,
                $item->category,
                implode(' ', $item->tags ?? []),
                collect($item->translations ?? [])->flatten()->implode(' '),
            ]), $query))
            ->take($limit);
    }
}
