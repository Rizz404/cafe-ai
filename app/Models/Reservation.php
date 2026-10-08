<?php

namespace App\Models;

use App\Modules\Reservation\Enums\ReservationStatus;
use App\Modules\Reservation\Support\TableAvailability;
use App\Modules\Reservation\Enums\Occasion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reference',
    'cafe_id',
    'seating_area_id',
    'conversation_id',
    'guest_name',
    'guest_email',
    'guest_phone',
    'contact_type',
    'reservation_date',
    'time_slot',
    'guests',
    'occasion',
    'deposit_total',
    'status',
    'notes',
])]
class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = ReservationStatus::Pending->value;

    public const STATUS_CONFIRMED = ReservationStatus::Confirmed->value;

    public const STATUS_CANCELLED = ReservationStatus::Cancelled->value;

    /**
     * Occasions a guest can mention so the team can prepare the table.
     */
    public const OCCASIONS = [
        Occasion::Birthday->value,
        Occasion::Date->value,
        Occasion::Family->value,
        Occasion::Meeting->value,
        Occasion::Work->value,
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'deposit_total' => 'decimal:2',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function seatingArea(): BelongsTo
    {
        return $this->belongsTo(SeatingArea::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * "11:00–13:00" for a reservation that starts at 11:00.
     */
    public function getTimeRangeAttribute(): string
    {
        return TableAvailability::slotRange($this->time_slot);
    }

    /**
     * A short, unambiguous, non-sequential code guests can quote to staff.
     */
    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $reference = 'RS-'.collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
