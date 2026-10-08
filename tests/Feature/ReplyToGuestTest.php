<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\MenuItem;
use App\Modules\Assistant\Gateway\ChatCompletionResult;
use App\Modules\Assistant\Gateway\FakeModelGateway;
use App\Modules\Assistant\Gateway\GatewayException;
use App\Modules\Assistant\Gateway\ModelGateway;
use App\Modules\Conversation\Actions\ReplyToGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The turn loop against the fake model gateway: no HTTP involved.
 */
class ReplyToGuestTest extends TestCase
{
    use RefreshDatabase;

    private Cafe $cafe;

    private Conversation $conversation;

    private FakeModelGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cafe = Cafe::factory()->create();
        $this->conversation = Conversation::factory()->for($this->cafe)->create(['locale' => 'en']);
        $this->gateway = new FakeModelGateway;

        $this->app->instance(ModelGateway::class, $this->gateway);
    }

    public function test_a_plain_answer_is_stored_as_the_assistant_message(): void
    {
        $this->gateway->push(new ChatCompletionResult('Welcome in!'));

        $message = app(ReplyToGuest::class)->handle($this->cafe, $this->conversation, 'Hello');

        $this->assertSame(ConversationMessage::ROLE_ASSISTANT, $message->role);
        $this->assertSame('Welcome in!', $message->content);
        $this->assertSame(['Hello', 'Welcome in!'], $this->conversation->messages()->pluck('content')->all());
    }

    public function test_a_tool_call_is_run_against_the_cafe_and_its_card_is_attached(): void
    {
        MenuItem::factory()->for($this->cafe)->create(['name' => 'Cafe Latte', 'slug' => 'cafe-latte', 'category' => 'coffee', 'price' => 32000]);

        $this->gateway
            ->push(new ChatCompletionResult(null, [[
                'id' => 'call_1',
                'function' => ['name' => 'search_menu', 'arguments' => json_encode(['query' => 'latte'])],
            ]]))
            ->push(new ChatCompletionResult('Our Cafe Latte is lovely.'));

        $message = app(ReplyToGuest::class)->handle($this->cafe, $this->conversation, 'Do you have a latte?');

        $this->assertSame('Our Cafe Latte is lovely.', $message->content);
        $this->assertSame('menu_results', $message->ui_payload[0]['type']);
        $this->assertSame('cafe-latte', $message->ui_payload[0]['items'][0]['menu_item_slug']);
        $this->assertSame('search_menu', $message->tool_calls[0]['name']);
        $this->assertCount(2, $this->gateway->requests);
    }

    public function test_the_model_is_sent_the_whitelisted_tools_and_a_cafe_specific_prompt(): void
    {
        $this->gateway->push(new ChatCompletionResult('Hi!'));

        app(ReplyToGuest::class)->handle($this->cafe, $this->conversation, 'Hello');

        $request = $this->gateway->requests[0];

        $this->assertCount(7, $request->tools);
        $this->assertSame('system', $request->messages[0]['role']);
        $this->assertStringContainsString($this->cafe->name, $request->messages[0]['content']);
    }

    public function test_a_gateway_failure_removes_the_guest_message_so_a_retry_does_not_duplicate_it(): void
    {
        $this->gateway->push(new GatewayException('provider down'));

        try {
            app(ReplyToGuest::class)->handle($this->cafe, $this->conversation, 'Hello');
            $this->fail('The failure should reach the caller.');
        } catch (GatewayException) {
            $this->assertSame(0, $this->conversation->messages()->count());
        }
    }

    public function test_a_handed_over_conversation_is_not_sent_to_the_model(): void
    {
        $this->conversation->update(['status' => Conversation::STATUS_HANDED_OVER]);

        $message = app(ReplyToGuest::class)->handle($this->cafe, $this->conversation, 'Anyone there?');

        $this->assertSame(ConversationMessage::ROLE_SYSTEM, $message->role);
        $this->assertSame([], $this->gateway->requests);
    }
}
