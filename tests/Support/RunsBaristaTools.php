<?php

namespace Tests\Support;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolExecutor;

/**
 * Runs one AI Barista tool the way the model would, against real cafe data.
 */
trait RunsBaristaTools
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{text: string, ui: ?array<string, mixed>}
     */
    protected function runTool(Cafe $cafe, Conversation $conversation, string $name, array $input): array
    {
        $result = app(ToolExecutor::class)->execute(new ToolContext($cafe, $conversation, $conversation->locale), $name, $input);

        return ['text' => $result->text, 'ui' => $result->ui];
    }
}
