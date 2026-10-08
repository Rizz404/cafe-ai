<?php

namespace App\Modules\Assistant\Gateway;

/**
 * What the model answered: text, tool calls, or both.
 */
final readonly class ChatCompletionResult
{
    /**
     * @param  list<array<string, mixed>>|null  $toolCalls
     */
    public function __construct(
        public ?string $content,
        public ?array $toolCalls = null,
    ) {}

    public function hasToolCalls(): bool
    {
        return ! empty($this->toolCalls);
    }
}
