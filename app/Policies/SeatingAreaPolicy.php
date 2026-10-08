<?php

namespace App\Policies;

use App\Models\SeatingArea;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SeatingAreaPolicy extends CafeScopedPolicy
{
    public function update(User $user, SeatingArea $seatingArea): Response
    {
        return $this->ownsCafe($user, $seatingArea->cafe_id);
    }

    public function delete(User $user, SeatingArea $seatingArea): Response
    {
        return $this->ownsCafe($user, $seatingArea->cafe_id);
    }
}
