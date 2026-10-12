<?php

namespace App\Modules\Assistant\Prompt;

/**
 * Catches an answer that states cafe data the model can only know from a tool:
 * a price, or a card it pretends to show.
 */
class AnswerValidator
{
    private const UNBACKED_DATA_PATTERN = '/(?:rp\.?|idr|usd|jpy|¥|\$)\s?\d|\d[\d.,]*\s?(?:rb|ribu|juta|jt|k)\b|\[[^\]]+\]/iu';

    public function __construct(private readonly PromptLibrary $library) {}

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
        return $this->library->load('notices')['unbacked_data'];
    }
}
