<?php

namespace App\Modules\Conversation\Enums;

/**
 * Whether the team still has to take over the conversation.
 */
enum HandoverStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
