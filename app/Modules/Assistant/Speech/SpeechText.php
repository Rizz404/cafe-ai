<?php

namespace App\Modules\Assistant\Speech;

/**
 * Turns a chat reply into something worth reading aloud: the markdown the
 * bubble renders (bold, bullets, headings) would otherwise be spoken as symbols.
 */
final class SpeechText
{
    public const MAX_CHARACTERS = 1200;

    public static function from(string $content): string
    {
        $lines = preg_split('/\R/u', $content) ?: [];

        $spoken = collect($lines)
            ->reject(fn (string $line) => preg_match('/^\s*(```|~~~)/', $line) === 1 || preg_match('/^\s*([-*_])(\s*\1){2,}\s*$/', $line) === 1)
            ->map(fn (string $line) => preg_replace(['/^\s{0,3}#{1,6}\s+/', '/^\s*([-*•]|\d+[.)])\s+/'], '', $line))
            ->map(fn (string $line) => preg_replace(['/`([^`]*)`/', '/(\*\*|__)(.+?)\1/u', '/(?<![\w*])\*(?!\s)([^*]+?)(?<!\s)\*(?![\w*])/u'], ['$1', '$2', '$1'], $line))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(fn (string $line) => preg_match('/[.!?。！？:;]$/u', $line) === 1 ? $line : $line.'.')
            ->implode(' ');

        return mb_strimwidth($spoken, 0, self::MAX_CHARACTERS, '…');
    }
}
