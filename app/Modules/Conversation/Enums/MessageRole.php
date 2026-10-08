<?php

namespace App\Modules\Conversation\Enums;

/**
 * Who wrote a conversation message.
 */
enum MessageRole: string
{
    case Guest = 'guest';
    case Assistant = 'assistant';
    case Staff = 'staff';
    case System = 'system';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
