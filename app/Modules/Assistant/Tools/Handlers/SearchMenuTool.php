<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Models\MenuItem;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;
use App\Modules\Menu\Queries\SearchMenu;

final class SearchMenuTool implements Tool
{
    public function __construct(private readonly SearchMenu $search) {}

    public function name(): string
    {
        return 'search_menu';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Search the cafe menu by keyword, category, dietary need or budget. Returns matching drinks and dishes with their real price, allergens and whether they are sold out. Use this whenever a guest asks what is on the menu, wants a recommendation, or describes a taste, diet or budget rather than naming one item.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Keywords such as "latte", "chocolate", "refreshing" — optional'],
                        'category' => [
                            'type' => 'string',
                            'enum' => MenuItem::categories(),
                            'description' => 'Optional category filter',
                        ],
                        'dietary' => [
                            'type' => 'string',
                            'enum' => MenuItem::DIETARY_TAGS,
                            'description' => 'Optional dietary requirement',
                        ],
                        'max_price' => ['type' => 'number', 'description' => 'Optional budget per item, in the cafe currency'],
                    ],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $items = $this->search->handle(
            $context->cafe,
            (string) ($arguments['query'] ?? ''),
            $arguments['category'] ?? null,
            $arguments['dietary'] ?? null,
            isset($arguments['max_price']) ? (float) $arguments['max_price'] : null,
        );

        if ($items->isEmpty()) {
            return new ToolResult('No menu item matches that request. Tell the guest honestly and suggest a nearby alternative from another search_menu call, or call request_human_handover for custom requests.');
        }

        $results = $items->map(fn (MenuItem $item) => CatalogSummaries::menuItem($item, $context->cafe, $context->locale))->values()->all();

        return ToolResult::json($results, ['type' => 'menu_results', 'items' => $results]);
    }
}
