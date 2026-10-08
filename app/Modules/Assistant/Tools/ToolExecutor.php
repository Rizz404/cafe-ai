<?php

namespace App\Modules\Assistant\Tools;

/**
 * Runs the tool the model asked for, if the registry knows it.
 */
class ToolExecutor
{
    public function __construct(private readonly ToolRegistry $registry) {}

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function execute(ToolContext $context, string $name, array $arguments): ToolResult
    {
        $tool = $this->registry->find($name);

        if (! $tool) {
            return new ToolResult("Unknown tool: {$name}");
        }

        return $tool->execute($context, $arguments);
    }
}
