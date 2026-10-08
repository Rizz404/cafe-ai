<?php

namespace App\Modules\Reservation\Actions;

use App\Models\Cafe;
use App\Models\SeatingArea;
use App\Modules\Reservation\Support\TableAvailability;
use App\Modules\Reservation\Validation\ReservationRules;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Turns the guest's already well-formed request into the seating area and
 * date it names, or tells them in their own language what does not fit.
 */
class ResolveReservationTarget
{
    public function __construct(private readonly TableAvailability $availability) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: SeatingArea, 1: CarbonImmutable}
     *
     * @throws ValidationException
     */
    public function handle(Cafe $cafe, array $data, string $locale): array
    {
        $area = $cafe->seatingAreas()->where('is_active', true)->where('slug', $data['seating_area_slug'])->first();

        if (! $area) {
            throw ValidationException::withMessages(['seating_area_slug' => ReservationRules::message('area_unknown', $locale)]);
        }

        $date = CarbonImmutable::parse($data['reservation_date'], $cafe->timezone)->startOfDay();
        $today = CarbonImmutable::now($cafe->timezone)->startOfDay();

        if ($today->diffInDays($date) > TableAvailability::MAX_ADVANCE_DAYS) {
            throw ValidationException::withMessages([
                'reservation_date' => ReservationRules::message('date_far', $locale, ['max' => (string) TableAvailability::MAX_ADVANCE_DAYS]),
            ]);
        }

        if (! in_array($data['time_slot'], $this->availability->slotsFor($cafe), true)) {
            throw ValidationException::withMessages(['time_slot' => ReservationRules::message('slot_unknown', $locale)]);
        }

        if ($this->availability->isSlotInPast($cafe, $date, $data['time_slot'])) {
            throw ValidationException::withMessages(['time_slot' => ReservationRules::message('slot_past', $locale)]);
        }

        if (! $this->availability->fitsParty($area, (int) $data['guests'])) {
            throw ValidationException::withMessages(['guests' => ReservationRules::message('capacity', $locale)]);
        }

        return [$area, $date];
    }
}
