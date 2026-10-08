<?php

namespace App\Modules\Assistant\Tools;

use App\Models\Cafe;
use App\Models\Conversation;

/**
 * What a tool may act on: one cafe, one conversation, in the guest's language.
 */
final readonly class ToolContext
{
    public function __construct(
        public Cafe $cafe,
        public Conversation $conversation,
        public string $locale,
    ) {}
}
