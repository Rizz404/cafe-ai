<?php

namespace App\Models;

use App\Modules\Conversation\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'cafe_id',
    'guest_token',
    'guest_name',
    'guest_email',
    'locale',
    'status',
    'handover_summary',
    'current_scene',
    'selected_menu_item_id',
    'selected_seating_area_id',
    'selected_facility_id',
    'reservation_state',
    'last_message_at',
])]
class Conversation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = ConversationStatus::Active->value;

    public const STATUS_HANDED_OVER = ConversationStatus::HandedOver->value;

    public const STATUS_CLOSED = ConversationStatus::Closed->value;

    /**
     * The UI scenes the guest can be in while talking to the AI Barista.
     */
    public const SCENES = ['home', 'menu', 'menu_item', 'seating', 'seating_area', 'facilities', 'facility_detail', 'reservation', 'handover'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'reservation_state' => 'array',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function selectedMenuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'selected_menu_item_id');
    }

    public function selectedSeatingArea(): BelongsTo
    {
        return $this->belongsTo(SeatingArea::class, 'selected_seating_area_id');
    }

    public function selectedFacility(): BelongsTo
    {
        return $this->belongsTo(CafeKnowledgeItem::class, 'selected_facility_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function handoverRequest(): HasOne
    {
        return $this->hasOne(HandoverRequest::class)->latestOfMany();
    }

    public function isHandedOver(): bool
    {
        return $this->status === self::STATUS_HANDED_OVER;
    }
}
