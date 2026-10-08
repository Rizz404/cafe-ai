<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy extends CafeScopedPolicy
{
    public function update(User $user, Reservation $reservation): Response
    {
        return $this->ownsCafe($user, $reservation->cafe_id);
    }
}
