<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Staff manage the records of their own cafe and nothing else. A record that
 * belongs to another cafe is answered with "not found", so its existence is
 * never revealed.
 */
abstract class CafeScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentCafe() !== null;
    }

    public function create(User $user): bool
    {
        return $user->currentCafe() !== null;
    }

    protected function ownsCafe(User $user, ?int $cafeId): Response
    {
        $cafe = $user->currentCafe();

        return $cafe !== null && $cafe->id === $cafeId
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
