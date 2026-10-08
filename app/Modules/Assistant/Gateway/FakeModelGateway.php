<?php

namespace App\Modules\Assistant\Gateway;

/**
 * Stands in for the provider in tests: answers with queued results, in order,
 * and remembers every request it was given.
 */
class FakeModelGateway implements ModelGateway
{
    /**
     * @var list<ChatCompletionRequest>
     */
    public array $requests = [];

    /**
     * @param  list<ChatCompletionResult|GatewayException>  $queue
     */
    public function __construct(private array $queue = []) {}

    public function push(ChatCompletionResult|GatewayException $next): static
    {
        $this->queue[] = $next;

        return $this;
    }

    public function complete(ChatCompletionRequest $request, TurnDeadline $deadline): ChatCompletionResult
    {
        $this->requests[] = $request;

        $next = array_shift($this->queue) ?? new ChatCompletionResult('');

        if ($next instanceof GatewayException) {
            throw $next;
        }

        return $next;
    }

    public function available(): bool
    {
        return true;
    }
}
