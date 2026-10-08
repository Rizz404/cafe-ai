<?php

namespace App\Modules\Assistant\Gateway;

use Illuminate\Support\Facades\Http;

/**
 * A self-hosted, OpenAI-compatible chat endpoint (LM Studio / Ollama over
 * Tailscale), called at /v1/chat/completions.
 *
 * The local model is a "thinking" model, so every request sends
 * reasoning_effort to skip the long reasoning pass (see config/assistant.php).
 */
class CompatibleChatCompletionsGateway implements ModelGateway
{
    public function complete(ChatCompletionRequest $request, TurnDeadline $deadline): ChatCompletionResult
    {
        $remaining = $deadline->remainingSeconds();

        if ($remaining < 1) {
            throw new GatewayException('Local LLM did not answer within the '.config('assistant.reply_budget_seconds').'s reply budget.');
        }

        $response = Http::withToken(config('services.local_llm.api_key'))
            ->timeout(min(config('assistant.request_timeout_seconds'), (int) ceil($remaining)))
            ->post(rtrim(config('services.local_llm.base_url'), '/').'/v1/chat/completions', [
                'model' => config('services.local_llm.model'),
                'messages' => $request->messages,
                'tools' => $request->tools,
                'tool_choice' => 'auto',
                'temperature' => config('assistant.temperature'),
                'reasoning_effort' => config('assistant.reasoning_effort'),
                'max_tokens' => config('assistant.max_tokens'),
            ]);

        if ($response->failed()) {
            throw new GatewayException("Local LLM request failed: HTTP {$response->status()} — {$response->body()}");
        }

        $message = $response->json('choices.0.message', []);
        $finishReason = $response->json('choices.0.finish_reason');

        if ($finishReason === 'length' && empty($message['tool_calls'])) {
            throw new GatewayException('Local LLM ran out of tokens mid-thought before it could respond. Increase assistant.max_tokens.');
        }

        return new ChatCompletionResult($message['content'] ?? null, $message['tool_calls'] ?? null);
    }

    public function available(): bool
    {
        return filled(config('services.local_llm.base_url'));
    }
}
