<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Models\CafeKnowledgeItem;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;
use App\Modules\Knowledge\Queries\SearchKnowledge;

final class SearchKnowledgeTool implements Tool
{
    public function __construct(private readonly SearchKnowledge $search) {}

    public function name(): string
    {
        return 'search_knowledge';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Search the cafe\'s approved knowledge base for facts about the cafe: policies, facilities, events and catering, location, and FAQs. Always use this instead of answering cafe-fact questions from memory. Returns up to 5 matching entries.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Keywords from the guest question, e.g. "wifi" or "private event"'],
                        'category' => [
                            'type' => 'string',
                            'enum' => ['general', 'facilities', 'policies', 'events', 'location', 'faq'],
                            'description' => 'Optional category filter',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $items = $this->search->handle($context->cafe, (string) ($arguments['query'] ?? ''), $arguments['category'] ?? null);

        if ($items->isEmpty()) {
            return new ToolResult('No matching knowledge base entry was found. Do not guess the answer — tell the guest you will check with the team, or call request_human_handover.');
        }

        return ToolResult::json($items->map(fn (CafeKnowledgeItem $item) => [
            'category' => $item->category,
            'title' => $item->translatedTitle($context->locale),
            'body' => $item->translatedBody($context->locale),
        ])->values()->all());
    }
}
