<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Small text helpers for the comma-separated fields of the admin forms.
 */
final class Text
{
    /**
     * "Wi-Fi, Quiet corner" becomes ['wi_fi', 'quiet_corner'].
     *
     * @return list<string>
     */
    public static function slugList(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($entry) => Str::of($entry)->trim()->slug('_')->toString())
            ->filter()
            ->values()
            ->all();
    }

    /**
     * "birthday cake, window" becomes ['birthday cake', 'window'].
     *
     * @return list<string>
     */
    public static function list(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($entry) => trim($entry))
            ->filter()
            ->values()
            ->all();
    }
}
