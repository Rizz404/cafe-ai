<?php

namespace App\Modules\Conversation\Actions;

use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Models\User;

/**
 * The team is done with the guest: the AI Barista is back in the conversation.
 */
class ResolveHandover
{
    public function handle(HandoverRequest $handover, User $staff): void
    {
        $handover->update([
            'status' => HandoverRequest::STATUS_RESOLVED,
            'resolved_at' => now(),
            'assigned_to' => $staff->id,
        ]);

        $handover->conversation->update(['status' => Conversation::STATUS_ACTIVE]);
    }
}
