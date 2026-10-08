<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolExecutor;
use App\Modules\Assistant\Tools\ToolRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_model_is_offered_exactly_the_whitelisted_tools_in_order(): void
    {
        $names = collect(app(ToolRegistry::class)->definitions())->pluck('function.name')->all();

        $this->assertSame([
            'search_knowledge',
            'search_menu',
            'get_menu_item',
            'search_seating',
            'check_table_availability',
            'create_reservation_request',
            'request_human_handover',
        ], $names);
    }

    public function test_every_definition_is_an_openai_compatible_function(): void
    {
        foreach (app(ToolRegistry::class)->definitions() as $definition) {
            $this->assertSame('function', $definition['type']);
            $this->assertNotEmpty($definition['function']['description']);
            $this->assertSame('object', $definition['function']['parameters']['type']);
        }
    }

    public function test_a_name_outside_the_whitelist_is_never_resolved_to_a_class(): void
    {
        $this->assertNull(app(ToolRegistry::class)->find('App\\Models\\User'));
        $this->assertNull(app(ToolRegistry::class)->find('drop_all_tables'));
    }

    public function test_an_unknown_tool_call_gets_a_plain_answer_and_no_card(): void
    {
        $conversation = Conversation::factory()->for(Cafe::factory())->create();

        $result = app(ToolExecutor::class)->execute(
            new ToolContext($conversation->cafe, $conversation, 'en'),
            'drop_all_tables',
            [],
        );

        $this->assertSame('Unknown tool: drop_all_tables', $result->text);
        $this->assertNull($result->ui);
    }
}
