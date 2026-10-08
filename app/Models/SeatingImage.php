<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'seating_area_id',
    'image_path',
    'image_url',
    'tags',
    'alt_text',
    'sort_order',
])]
class SeatingImage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function seatingArea(): BelongsTo
    {
        return $this->belongsTo(SeatingArea::class);
    }

    public function getImageSourceAttribute(): ?string
    {
        if ($this->image_url) {
            return str_starts_with($this->image_url, 'http') ? $this->image_url : asset($this->image_url);
        }

        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }
}
