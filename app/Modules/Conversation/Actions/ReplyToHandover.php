<?php

namespace App\Modules\Conversation\Actions;

use App\Models\ConversationMessage;
use App\Models\HandoverRequest;

/**
 * A team member answers the guest in a conversation the barista handed over.
 */
class ReplyToHandover
{
    public function handle(HandoverRequest $handover, string $message): ConversationMessage
    {
        return $handover->conversation->messages()->create([
            'role' => ConversationMessage::ROLE_STAFF,
            'content' => $message,
        ]);
    }
}
