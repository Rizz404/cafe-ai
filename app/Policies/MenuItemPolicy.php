<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MenuItemPolicy extends CafeScopedPolicy
{
    public function update(User $user, MenuItem $menuItem): Response
    {
        return $this->ownsCafe($user, $menuItem->cafe_id);
    }

    public function delete(User $user, MenuItem $menuItem): Response
    {
        return $this->ownsCafe($user, $menuItem->cafe_id);
    }
}
