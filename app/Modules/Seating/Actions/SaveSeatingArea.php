<?php

namespace App\Modules\Seating\Actions;

use App\Models\Cafe;
use App\Models\SeatingArea;
use Illuminate\Support\Str;

/**
 * Creates a seating area or updates one, together with the photos added or
 * removed in the same form. A new area gets a slug from its name; an existing
 * one keeps its slug so guest links never break.
 */
class SaveSeatingArea
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $imageIdsToDelete
     * @param  list<array{url: string, alt?: ?string, tags?: ?string}>  $newImages
     */
    public function handle(Cafe $cafe, array $attributes, array $imageIdsToDelete, array $newImages, ?SeatingArea $seatingArea = null): SeatingArea
    {
        if ($seatingArea) {
            $seatingArea->update($attributes);
        } else {
            $seatingArea = $cafe->seatingAreas()->create([...$attributes, 'slug' => $this->uniqueSlug($cafe, $attributes['name'])]);
        }

        $this->syncImages($seatingArea, $imageIdsToDelete, $newImages);

        return $seatingArea;
    }

    private function uniqueSlug(Cafe $cafe, string $name): string
    {
        $base = Str::slug($name) ?: 'area';
        $slug = $base;
        $suffix = 1;

        while ($cafe->seatingAreas()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  list<int>  $deleteIds
     * @param  list<array{url: string, alt?: ?string, tags?: ?string}>  $newImages
     */
    private function syncImages(SeatingArea $seatingArea, array $deleteIds, array $newImages): void
    {
        if ($deleteIds !== []) {
            $seatingArea->images()->whereIn('id', $deleteIds)->delete();
        }

        $sort = $seatingArea->images()->max('sort_order') + 1;

        foreach ($newImages as $row) {
            $seatingArea->images()->create([
                'image_url' => $row['url'],
                'alt_text' => $row['alt'] ?? $seatingArea->name,
                'tags' => collect(explode(',', $row['tags'] ?? ''))->map(fn ($t) => trim($t))->filter()->values()->all(),
                'sort_order' => $sort++,
            ]);
        }
    }
}
