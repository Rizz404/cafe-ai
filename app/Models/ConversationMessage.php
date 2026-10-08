<?php

namespace App\Models;

use App\Modules\Conversation\Enums\MessageRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'role',
    'content',
    'ui_payload',
    'tool_calls',
])]
class ConversationMessage extends Model
{
    use HasFactory;

    public const ROLE_GUEST = MessageRole::Guest->value;

    public const ROLE_ASSISTANT = MessageRole::Assistant->value;

    public const ROLE_STAFF = MessageRole::Staff->value;

    public const ROLE_SYSTEM = MessageRole::System->value;

    protected function casts(): array
    {
        return [
            'ui_payload' => 'array',
            'tool_calls' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
