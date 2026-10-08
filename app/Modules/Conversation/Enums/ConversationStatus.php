<?php

namespace App\Modules\Conversation\Enums;

/**
 * Who is answering the guest.
 */
enum ConversationStatus: string
{
    case Active = 'active';
    case HandedOver = 'handed_over';
    case Closed = 'closed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
