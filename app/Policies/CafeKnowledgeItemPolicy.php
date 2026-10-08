<?php

namespace App\Policies;

use App\Models\CafeKnowledgeItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CafeKnowledgeItemPolicy extends CafeScopedPolicy
{
    public function update(User $user, CafeKnowledgeItem $knowledgeItem): Response
    {
        return $this->ownsCafe($user, $knowledgeItem->cafe_id);
    }

    public function delete(User $user, CafeKnowledgeItem $knowledgeItem): Response
    {
        return $this->ownsCafe($user, $knowledgeItem->cafe_id);
    }
}
