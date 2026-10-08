<?php

namespace App\Modules\Assistant\Prompt;

/**
 * Catches an answer that states cafe data the model can only know from a tool:
 * a price, or a card it pretends to show.
 */
class AnswerValidator
{
    private const UNBACKED_DATA_PATTERN = '/(?:rp\.?|idr|usd|jpy|¥|\$)\s?\d|\d[\d.,]*\s?(?:rb|ribu|juta|jt|k)\b|\[[^\]]+\]/iu';

    /**
     * Whether the text states a price or shows a card without a tool call.
     */
    public function claimsToolData(string $text): bool
    {
        return preg_match(self::UNBACKED_DATA_PATTERN, $text) === 1;
    }

    /**
     * What the model is told when it answered that way, to make it call a tool.
     */
    public function correctionNotice(): string
    {
        return '[System notice: your last reply stated a price or pretended to show menu or table cards without calling a tool. Never state a menu price, a sold-out status or table availability from memory. Call search_menu, get_menu_item, search_seating or check_table_availability now, or ask the guest for the details you still need.]';
    }
}
