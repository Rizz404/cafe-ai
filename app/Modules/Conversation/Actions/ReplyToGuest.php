<?php

namespace App\Modules\Conversation\Actions;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Modules\Assistant\Gateway\ChatCompletionRequest;
use App\Modules\Assistant\Gateway\ChatCompletionResult;
use App\Modules\Assistant\Gateway\ModelGateway;
use App\Modules\Assistant\Gateway\TurnDeadline;
use App\Modules\Assistant\Prompt\AnswerValidator;
use App\Modules\Assistant\Prompt\PromptComposer;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolExecutor;
use App\Modules\Assistant\Tools\ToolRegistry;
use App\Modules\Conversation\Support\ContentGuard;

/**
 * Orchestrates one guest turn: runs the tool-use loop against a single cafe's
 * data through the model gateway, persists the conversation, and returns the
 * assistant message (with any UI payload to render).
 */
class ReplyToGuest
{
    public function __construct(
        private readonly ContentGuard $guard,
        private readonly ModelGateway $gateway,
        private readonly ToolRegistry $registry,
        private readonly ToolExecutor $executor,
        private readonly PromptComposer $prompt,
        private readonly AnswerValidator $validator,
    ) {}

    public function handle(Cafe $cafe, Conversation $conversation, string $guestMessage): ConversationMessage
    {
        $guestRecord = $conversation->messages()->create([
            'role' => ConversationMessage::ROLE_GUEST,
            'content' => $guestMessage,
        ]);

        if ($conversation->isHandedOver()) {
            return $conversation->messages()->create([
                'role' => ConversationMessage::ROLE_SYSTEM,
                'content' => 'This conversation is with the cafe team now. A team member will respond shortly.',
            ]);
        }

        if ($this->guard->isOffensive($guestMessage)) {
            return $this->refuse($conversation, $guestMessage);
        }

        try {
            return $this->answer($cafe, $conversation);
        } catch (\Throwable $e) {
            // The guest keeps the text on screen and can retry; leaving it here would duplicate it.
            $guestRecord->delete();

            throw $e;
        }
    }

    /**
     * Answers with the fixed refusal instead of asking the model.
     */
    private function refuse(Conversation $conversation, string $guestMessage): ConversationMessage
    {
        $conversation->update(['last_message_at' => now()]);

        return $conversation->messages()->create([
            'role' => ConversationMessage::ROLE_ASSISTANT,
            'content' => $this->guard->refusal($this->guard->detectLocale($guestMessage, $conversation->locale)),
        ]);
    }

    private function lastGuestMessage(Conversation $conversation): string
    {
        return (string) $conversation->messages()->where('role', ConversationMessage::ROLE_GUEST)->latest('id')->value('content');
    }

    private function answer(Cafe $cafe, Conversation $conversation): ConversationMessage
    {
        $context = new ToolContext($cafe, $conversation, $conversation->locale);
        $definitions = $this->registry->definitions();

        $messages = [
            ['role' => 'system', 'content' => $this->prompt->systemPrompt($cafe, $conversation)],
            ...$this->prompt->withScopeReminder($this->history($conversation), $cafe, $conversation->locale),
        ];

        $toolLog = [];
        $uiPayloads = [];
        $deadline = TurnDeadline::afterSeconds(config('assistant.reply_budget_seconds'));

        $result = $this->gateway->complete(new ChatCompletionRequest($messages, $definitions), $deadline);

        if (! $result->hasToolCalls() && $this->validator->claimsToolData($result->content ?? '')) {
            $messages[] = ['role' => 'assistant', 'content' => $result->content];
            $messages[] = ['role' => 'user', 'content' => $this->validator->correctionNotice()];

            $result = $this->gateway->complete(new ChatCompletionRequest($messages, $definitions), $deadline);
        }

        $iterations = 0;
        while ($result->hasToolCalls() && $iterations < config('assistant.max_tool_iterations')) {
            $iterations++;

            $messages[] = [
                'role' => 'assistant',
                'content' => $result->content ?? '',
                'tool_calls' => $result->toolCalls,
            ];

            foreach ($result->toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $input = json_decode($call['function']['arguments'] ?? '{}', true) ?? [];

                $toolResult = $this->executor->execute($context, $name, $input);

                $toolLog[] = ['name' => $name, 'input' => $input];
                if ($toolResult->ui !== null) {
                    $uiPayloads[] = $toolResult->ui;
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => $toolResult->text,
                ];
            }

            $result = $this->gateway->complete(new ChatCompletionRequest($messages, $definitions), $deadline);
        }

        return $this->record($conversation, $result, $toolLog, $uiPayloads);
    }

    /**
     * @param  list<array{name: string, input: array<string, mixed>}>  $toolLog
     * @param  list<array<string, mixed>>  $uiPayloads
     */
    private function record(Conversation $conversation, ChatCompletionResult $result, array $toolLog, array $uiPayloads): ConversationMessage
    {
        $text = trim((string) ($result->content ?? ''));

        if ($this->guard->isOffensive($text)) {
            return $this->refuse($conversation, $this->lastGuestMessage($conversation));
        }

        // A tool (e.g. request_human_handover) may have changed the
        // conversation's status/summary directly in the DB this turn.
        $conversation->refresh();
        $conversation->update(['last_message_at' => now()]);

        return $conversation->messages()->create([
            'role' => ConversationMessage::ROLE_ASSISTANT,
            'content' => $text !== '' ? $text : null,
            'ui_payload' => $uiPayloads !== [] ? $uiPayloads : null,
            'tool_calls' => $toolLog !== [] ? $toolLog : null,
        ]);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function history(Conversation $conversation): array
    {
        return $conversation->messages()
            ->whereIn('role', [ConversationMessage::ROLE_GUEST, ConversationMessage::ROLE_ASSISTANT])
            ->orderBy('created_at')
            ->get()
            ->map(fn (ConversationMessage $message) => [
                'role' => $message->role === ConversationMessage::ROLE_GUEST ? 'user' : 'assistant',
                'content' => (string) $message->content,
            ])
            ->filter(fn (array $m) => $m['content'] !== '')
            ->values()
            ->all();
    }
}
