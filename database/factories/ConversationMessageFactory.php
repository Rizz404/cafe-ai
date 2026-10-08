<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Modules\Conversation\Enums\MessageRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationMessage>
 */
class ConversationMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'role' => MessageRole::Guest->value,
            'content' => fake()->sentence(),
        ];
    }

    public function from(MessageRole $role): static
    {
        return $this->state(fn () => ['role' => $role->value]);
    }
}
