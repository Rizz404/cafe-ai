<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;

final class GetMenuItemTool implements Tool
{
    public function name(): string
    {
        return 'get_menu_item';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Get the full details of one menu item by its slug (from a previous search_menu result): description, price, allergens, serving style and whether it is sold out. Use this when the guest asks about a specific drink or dish.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'menu_item_slug' => ['type' => 'string'],
                    ],
                    'required' => ['menu_item_slug'],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $item = CatalogSummaries::menuItemBySlug($context->cafe, (string) ($arguments['menu_item_slug'] ?? ''));

        if (! $item) {
            return new ToolResult('Menu item not found.');
        }

        $detail = [
            ...CatalogSummaries::menuItem($item, $context->cafe, $context->locale),
            'description' => $item->translatedDescription($context->locale),
            'calories' => $item->calories,
            'note' => 'price is the current menu price; allergens are guidance only and the kitchen must confirm for guests with a serious allergy.',
        ];

        return ToolResult::json($detail, ['type' => 'menu_detail', 'item' => $detail]);
    }
}
