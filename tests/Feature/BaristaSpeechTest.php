<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Modules\Assistant\Speech\SpeechText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BaristaSpeechTest extends TestCase
{
    use RefreshDatabase;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config([
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.api_key' => 'router-key',
            'services.openrouter.tts_model' => 'google/test-tts',
            'services.openrouter.tts_voice' => 'Sulafat',
        ]);

        $cafe = Cafe::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'city' => 'Jakarta', 'country' => 'Indonesia', 'currency' => 'IDR', 'default_locale' => 'en']);
        $this->conversation = Conversation::create(['cafe_id' => $cafe->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en', 'status' => Conversation::STATUS_ACTIVE]);
    }

    private function say(string $role, string $content): ConversationMessage
    {
        return $this->conversation->messages()->create(['role' => $role, 'content' => $content]);
    }

    /**
     * @return array{guest_token: string, message_id: int}
     */
    private function payloadFor(ConversationMessage $message): array
    {
        return ['guest_token' => $this->conversation->guest_token, 'message_id' => $message->id];
    }

    public function test_a_barista_reply_is_spoken_as_audio(): void
    {
        Http::fake(['openrouter.test/*' => Http::response('pcm-bytes', 200, ['Content-Type' => 'audio/pcm;rate=24000;channels=1'])]);
        $message = $this->say(ConversationMessage::ROLE_ASSISTANT, 'Welcome to **Demo**!');

        $response = $this->postJson('/demo/barista/speech', $this->payloadFor($message))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/wav');

        $wav = $response->getContent();
        $this->assertSame('RIFF', substr($wav, 0, 4));
        $this->assertSame('WAVE', substr($wav, 8, 4));
        $this->assertSame(24000, unpack('V', substr($wav, 24, 4))[1]);
        $this->assertSame(1, unpack('v', substr($wav, 22, 2))[1]);
        $this->assertSame('pcm-bytes', substr($wav, 44));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://openrouter.test/api/v1/audio/speech'
            && $request->hasHeader('Authorization', 'Bearer router-key')
            && $request['model'] === 'google/test-tts'
            && $request['voice'] === 'Sulafat'
            && $request['input'] === 'Welcome to Demo!'
            && $request['response_format'] === 'pcm');
    }

    public function test_a_line_that_was_already_spoken_is_not_paid_for_twice(): void
    {
        Http::fake(['openrouter.test/*' => Http::response('pcm-bytes')]);
        $first = $this->say(ConversationMessage::ROLE_ASSISTANT, 'Welcome to Demo!');
        $second = $this->say(ConversationMessage::ROLE_ASSISTANT, 'Welcome to Demo!');

        $this->postJson('/demo/barista/speech', $this->payloadFor($first))->assertOk();
        $this->postJson('/demo/barista/speech', $this->payloadFor($second))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_only_barista_replies_of_the_own_conversation_can_be_spoken(): void
    {
        Http::fake();
        $guestMessage = $this->say(ConversationMessage::ROLE_GUEST, 'Say something rude');
        $staffMessage = $this->say(ConversationMessage::ROLE_STAFF, 'We saved you a table.');

        $this->postJson('/demo/barista/speech', $this->payloadFor($guestMessage))->assertNotFound();
        $this->postJson('/demo/barista/speech', $this->payloadFor($staffMessage))->assertNotFound();
        $this->postJson('/demo/barista/speech', [...$this->payloadFor($guestMessage), 'guest_token' => (string) Str::uuid()])->assertUnprocessable();

        Http::assertNothingSent();
    }

    public function test_the_barista_stays_quiet_when_the_voice_provider_fails(): void
    {
        Http::fake(['openrouter.test/*' => Http::response('nope', 500)]);
        $message = $this->say(ConversationMessage::ROLE_ASSISTANT, 'Welcome to Demo!');

        $this->postJson('/demo/barista/speech', $this->payloadFor($message))->assertNoContent();
    }

    public function test_the_barista_stays_quiet_without_a_voice_configured(): void
    {
        Http::fake();
        config(['services.openrouter.api_key' => null]);
        $message = $this->say(ConversationMessage::ROLE_ASSISTANT, 'Welcome to Demo!');

        $this->postJson('/demo/barista/speech', $this->payloadFor($message))->assertNoContent();

        Http::assertNothingSent();
    }

    public function test_speech_is_rate_limited_per_conversation(): void
    {
        Http::fake(['openrouter.test/*' => Http::response('pcm-bytes')]);
        $message = $this->say(ConversationMessage::ROLE_ASSISTANT, 'Welcome to Demo!');

        foreach (range(1, 12) as $attempt) {
            $this->postJson('/demo/barista/speech', $this->payloadFor($message))->assertOk();
        }

        $this->postJson('/demo/barista/speech', $this->payloadFor($message))->assertTooManyRequests();
    }

    public function test_markdown_is_removed_before_a_reply_is_read_aloud(): void
    {
        $reply = "## Our coffee\n\nTry the **Cafe Latte**:\n- Smooth and milky\n- Served *hot*\n1. Order at the bar\n\n---\n`Enjoy`";

        $this->assertSame(
            'Our coffee. Try the Cafe Latte: Smooth and milky. Served hot. Order at the bar. Enjoy.',
            SpeechText::from($reply),
        );
    }
}
