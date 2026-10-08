<?php

namespace App\Models;

use App\Modules\Seating\Enums\AreaType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'cafe_id',
    'name',
    'slug',
    'description',
    'translations',
    'area_type',
    'min_guests',
    'max_guests',
    'features',
    'reservation_fee',
    'minimum_spend',
    'is_active',
    'sort_order',
])]
class SeatingArea extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_INDOOR = AreaType::Indoor->value;

    public const TYPE_OUTDOOR = AreaType::Outdoor->value;

    public const TYPE_BAR = AreaType::Bar->value;

    public const TYPE_PRIVATE = AreaType::Private->value;

    /**
     * @return list<string>
     */
    public static function areaTypes(): array
    {
        return AreaType::values();
    }

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'features' => 'array',
            'reservation_fee' => 'decimal:2',
            'minimum_spend' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(SeatingImage::class)->orderBy('sort_order');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(TableInventory::class);
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
