<?php

namespace App\Modules\Assistant\Tools;

/**
 * One AI Barista tool. Handlers are registered explicitly in ToolRegistry; the
 * model only picks a name from that whitelist. The cafe and the conversation
 * always come from the server, never from the arguments the model sends.
 */
interface Tool
{
    public function name(): string;

    /**
     * Provider function definition (OpenAI-compatible "tools" entry).
     *
     * @return array{type: 'function', function: array{name: string, description: string, parameters: array<string, mixed>}}
     */
    public function definition(): array;

    /**
     * @param  array<string, mixed>  $arguments  Decoded JSON object from the model.
     */
    public function execute(ToolContext $context, array $arguments): ToolResult;
}
