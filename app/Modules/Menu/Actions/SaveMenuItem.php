<?php

namespace App\Modules\Menu\Actions;

use App\Models\Cafe;
use App\Models\MenuItem;
use Illuminate\Support\Str;

/**
 * Creates a menu item or updates one. A new item gets a slug from its name; an
 * existing one keeps its slug so guest links never break.
 */
class SaveMenuItem
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Cafe $cafe, array $attributes, ?MenuItem $menuItem = null): MenuItem
    {
        if ($menuItem) {
            $menuItem->update($attributes);

            return $menuItem;
        }

        return $cafe->menuItems()->create([...$attributes, 'slug' => $this->uniqueSlug($cafe, $attributes['name'])]);
    }

    private function uniqueSlug(Cafe $cafe, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $suffix = 1;

        while ($cafe->menuItems()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
