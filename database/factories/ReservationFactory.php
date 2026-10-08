<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\Reservation;
use App\Models\SeatingArea;
use App\Modules\Reservation\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Reservation::generateReference(),
            'cafe_id' => Cafe::factory(),
            'seating_area_id' => fn (array $attributes) => SeatingArea::factory()->create(['cafe_id' => $attributes['cafe_id']])->id,
            'guest_name' => fake()->name(),
            'guest_phone' => '+62 811 1111 222',
            'contact_type' => 'phone',
            'reservation_date' => now()->addDays(3)->toDateString(),
            'time_slot' => '11:00',
            'guests' => 2,
            'deposit_total' => 0,
            'status' => ReservationStatus::Pending->value,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => ReservationStatus::Confirmed->value]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => ReservationStatus::Cancelled->value]);
    }
}
