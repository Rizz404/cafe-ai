<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'custom_domain',
    'description',
    'translations',
    'address',
    'city',
    'country',
    'latitude',
    'longitude',
    'phone',
    'whatsapp',
    'email',
    'instagram',
    'timezone',
    'currency',
    'default_locale',
    'opening_time',
    'closing_time',
    'logo_path',
    'cover_path',
    'public_status',
])]
class Cafe extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cafe_users')
            ->using(CafeUser::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function seatingAreas(): HasMany
    {
        return $this->hasMany(SeatingArea::class);
    }

    public function knowledgeItems(): HasMany
    {
        return $this->hasMany(CafeKnowledgeItem::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function translatedDescription(string $locale): ?string
    {
        $value = $this->translations[$locale]['description'] ?? null;

        return filled($value) ? $value : $this->description;
    }

    /**
     * @param  Builder<Cafe>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('public_status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->public_status === 'published';
    }

    public function publicUrl(): string
    {
        return $this->custom_domain
            ? 'https://'.$this->custom_domain
            : url('/'.$this->slug);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'cafe';
        $slug = $base;
        $suffix = 1;

        while (static::withTrashed()->where('slug', $slug)->exists() || in_array($slug, static::reservedSlugs(), true)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return [
            'admin', 'login', 'logout', 'register', 'pricing', 'dashboard',
            'api', 'terms', 'privacy', 'forgot-password', 'reset-password',
            'verify-email', 'confirm-password', 'storage', 'build', 'account',
            'images',
        ];
    }
}
