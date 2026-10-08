<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Modules\Conversation\Enums\HandoverReason;
use App\Modules\Conversation\Enums\HandoverStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandoverRequest>
 */
class HandoverRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory()->handedOver(),
            'reason' => HandoverReason::SpecialRequest->value,
            'summary' => fake()->sentence(),
            'status' => HandoverStatus::Open->value,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['status' => HandoverStatus::Resolved->value, 'resolved_at' => now()]);
    }
}
