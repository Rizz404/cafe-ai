<?php

namespace App\Modules\Assistant\Gateway;

/**
 * Model provider boundary. Implementations never decide what a guest may see
 * and never retry a POST on their own.
 */
interface ModelGateway
{
    /**
     * @throws GatewayException
     */
    public function complete(ChatCompletionRequest $request, TurnDeadline $deadline): ChatCompletionResult;

    /**
     * Whether a provider endpoint is configured.
     */
    public function available(): bool;
}
