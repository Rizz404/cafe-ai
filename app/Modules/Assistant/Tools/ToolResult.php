<?php

namespace App\Modules\Assistant\Tools;

/**
 * What a tool hands back: the text the model reads, and an optional card
 * (UI payload) rendered on the guest's screen.
 */
final readonly class ToolResult
{
    /**
     * @param  array<string, mixed>|null  $ui
     */
    public function __construct(
        public string $text,
        public ?array $ui = null,
    ) {}

    /**
     * @param  array<mixed>  $data
     * @param  array<string, mixed>|null  $ui
     */
    public static function json(array $data, ?array $ui = null): self
    {
        return new self(json_encode($data, JSON_UNESCAPED_UNICODE), $ui);
    }
}
