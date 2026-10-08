<?php

namespace App\Models;

use App\Modules\Knowledge\Enums\KnowledgeCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * The approved knowledge base the AI Barista retrieves from. This is the
 * ONLY place cafe facts may come from: the model is never allowed to
 * answer a cafe-knowledge question from its own memory.
 */
#[Fillable([
    'cafe_id',
    'category',
    'title',
    'body',
    'translations',
    'tags',
    'image_url',
    'is_active',
    'sort_order',
])]
class CafeKnowledgeItem extends Model
{
    use HasFactory;

    public const CATEGORY_GENERAL = KnowledgeCategory::General->value;

    public const CATEGORY_FACILITIES = KnowledgeCategory::Facilities->value;

    public const CATEGORY_POLICIES = KnowledgeCategory::Policies->value;

    public const CATEGORY_EVENTS = KnowledgeCategory::Events->value;

    public const CATEGORY_LOCATION = KnowledgeCategory::Location->value;

    public const CATEGORY_FAQ = KnowledgeCategory::Faq->value;

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    /**
     * A short icon name for this entry, taken from its tags or title so the
     * facility cards can show a matching symbol without another column.
     */
    public function iconName(): string
    {
        $haystack = Str::lower($this->title.' '.implode(' ', $this->tags ?? []));

        foreach ([
            'wifi' => ['wifi', 'wi-fi', 'internet'],
            'power' => ['power', 'outlet', 'colokan', 'charging', 'listrik'],
            'workspace' => ['work', 'laptop', 'coworking', 'kerja', 'meeting'],
            'parking' => ['parking', 'parkir', 'car', 'motor'],
            'music' => ['music', 'musik', 'live', 'acoustic', 'akustik'],
            'event' => ['event', 'private', 'catering', 'workshop', 'acara', 'booking'],
            'delivery' => ['delivery', 'antar', 'takeaway', 'take-away', 'gofood', 'grab'],
            'pet' => ['pet', 'dog', 'cat', 'hewan', 'anjing', 'kucing'],
            'place' => ['nearby', 'terdekat', 'location', 'lokasi', 'station', 'stasiun'],
        ] as $icon => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($haystack, $needle)) {
                    return $icon;
                }
            }
        }

        return 'star';
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

    public function translatedTitle(string $locale): string
    {
        $value = $this->translations[$locale]['title'] ?? null;

        return filled($value) ? $value : $this->title;
    }

    public function translatedBody(string $locale): string
    {
        $value = $this->translations[$locale]['body'] ?? null;

        return filled($value) ? $value : $this->body;
    }
}
