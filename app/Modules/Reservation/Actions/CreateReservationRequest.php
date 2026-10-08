<?php

namespace App\Modules\Reservation\Actions;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Models\Reservation;
use App\Models\SeatingArea;
use App\Models\TableInventory;
use App\Modules\Reservation\Support\TableAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creates a pending reservation request and holds one table for it. Both the
 * reservation wizard and the AI Barista's reservation tool end up here.
 */
class CreateReservationRequest
{
    public function __construct(private readonly TableAvailability $availability) {}

    /**
     * Returns null when no table is free in that slot any more.
     *
     * @param  array{reservation_date: CarbonImmutable, time_slot: string, guests: int, occasion?: ?string, guest_name: string, guest_email?: ?string, guest_phone?: ?string, contact_type?: ?string, notes?: ?string}  $data
     */
    public function handle(Cafe $cafe, SeatingArea $area, array $data, ?Conversation $conversation = null): ?Reservation
    {
        return DB::transaction(function () use ($cafe, $area, $data, $conversation) {
            $quote = $this->availability->quote($area, $data['reservation_date'], $data['time_slot'], lockForUpdate: true);

            if (! $quote) {
                return null;
            }

            $reservation = Reservation::create([
                'reference' => Reservation::generateReference(),
                'cafe_id' => $cafe->id,
                'seating_area_id' => $area->id,
                'conversation_id' => $conversation?->id,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'contact_type' => $data['contact_type'] ?? null,
                'reservation_date' => $data['reservation_date']->toDateString(),
                'time_slot' => $data['time_slot'],
                'guests' => $data['guests'],
                'occasion' => $data['occasion'] ?? null,
                'deposit_total' => $quote['fee'],
                'status' => Reservation::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            TableInventory::where('seating_area_id', $reservation->seating_area_id)
                ->whereDate('reservation_date', $reservation->reservation_date->toDateString())
                ->where('time_slot', $reservation->time_slot)
                ->increment('reserved_tables');

            return $reservation;
        });
    }
}
