<?php

namespace App\Modules\Knowledge\Enums;

/**
 * How an approved knowledge entry is filed.
 */
enum KnowledgeCategory: string
{
    case General = 'general';
    case Facilities = 'facilities';
    case Policies = 'policies';
    case Events = 'events';
    case Location = 'location';
    case Faq = 'faq';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
