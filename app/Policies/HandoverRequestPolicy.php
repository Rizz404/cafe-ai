<?php

namespace App\Policies;

use App\Models\HandoverRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HandoverRequestPolicy extends CafeScopedPolicy
{
    public function view(User $user, HandoverRequest $handover): Response
    {
        return $this->ownsCafe($user, $handover->conversation->cafe_id);
    }

    public function update(User $user, HandoverRequest $handover): Response
    {
        return $this->ownsCafe($user, $handover->conversation->cafe_id);
    }
}
