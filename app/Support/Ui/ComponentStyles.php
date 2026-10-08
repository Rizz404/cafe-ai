<?php

namespace App\Support\Ui;

/**
 * Class strings for the UI kit (resources/views/components/ui). Every class is
 * written out in full so Tailwind can see it; never build one from a variable.
 * Colors come from theme tokens only (resources/css/theme.css).
 */
final class ComponentStyles
{
    private const BUTTON_BASE = 'inline-flex items-center justify-center gap-2 rounded-control font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:opacity-60';

    /** @var array<string, string> */
    private const BUTTON_VARIANTS = [
        'primary' => 'bg-primary text-on-primary hover:bg-primary-hover',
        'secondary' => 'border border-border-strong bg-surface text-muted hover:bg-surface-muted',
        'danger' => 'bg-danger text-on-primary hover:bg-danger-hover',
        'ghost' => 'text-muted hover:bg-surface-hover',
    ];

    /** @var array<string, string> */
    private const BUTTON_SIZES = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
    ];

    /** @var array<string, string> */
    private const ALERT_TONES = [
        'success' => 'border-success-border bg-success-soft text-success',
        'warning' => 'border-warning-border bg-warning-soft text-warning',
        'danger' => 'border-danger-border bg-danger-soft text-danger',
    ];

    /** @var array<string, string> */
    private const BADGE_TONES = [
        'neutral' => 'bg-surface-muted text-muted',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
    ];

    public static function button(string $variant = 'primary', string $size = 'md'): string
    {
        return self::BUTTON_BASE.' '.(self::BUTTON_VARIANTS[$variant] ?? self::BUTTON_VARIANTS['primary']).' '.(self::BUTTON_SIZES[$size] ?? self::BUTTON_SIZES['md']);
    }

    public static function alert(string $tone = 'success'): string
    {
        return 'rounded-control border px-4 py-3 text-sm '.(self::ALERT_TONES[$tone] ?? self::ALERT_TONES['success']);
    }

    public static function badge(string $tone = 'neutral'): string
    {
        return 'inline-flex items-center rounded-full px-2 py-0.5 text-xs '.(self::BADGE_TONES[$tone] ?? self::BADGE_TONES['neutral']);
    }

    /**
     * The shared look of text inputs, selects and textareas.
     */
    public static function control(bool $invalid = false): string
    {
        return 'mt-1 w-full rounded-control border bg-surface px-3 py-2 text-sm text-text placeholder:text-subtle focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-focus '
            .($invalid ? 'border-danger' : 'border-border-strong');
    }
}
