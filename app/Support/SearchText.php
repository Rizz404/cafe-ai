<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Keyword matching shared by the deterministic searches the AI Barista's tools
 * run over cafe data. No embeddings: a question either matches stored words or
 * it does not.
 */
final class SearchText
{
    /**
     * Lower-cases and drops hyphens and underscores, so "wifi" finds "Wi-Fi"
     * and "non coffee" finds "non_coffee".
     */
    public static function normalize(string $text): string
    {
        return trim(Str::lower(str_replace(['-', '_'], ['', ' '], $text)));
    }

    /**
     * Whether the haystack holds the whole query or any one of its words. An
     * empty query matches everything.
     */
    public static function matches(string $haystack, string $query): bool
    {
        if ($query === '') {
            return true;
        }

        $haystack = self::normalize($haystack);

        return Str::contains($haystack, $query)
            || collect(preg_split('/\s+/', $query))->filter()->contains(fn ($word) => Str::contains($haystack, $word));
    }
}
