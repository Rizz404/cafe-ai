<?php

namespace App\Modules\Assistant\Gateway;

/**
 * One call to the model: the conversation so far and the tools it may call.
 */
final readonly class ChatCompletionRequest
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     */
    public function __construct(
        public array $messages,
        public array $tools,
    ) {}
}
