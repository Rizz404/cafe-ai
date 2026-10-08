<?php

namespace App\Modules\Conversation\Actions;

use App\Models\Cafe;
use App\Models\Conversation;
use Illuminate\Support\Str;

class StartConversation
{
    public function handle(Cafe $cafe, string $locale = 'id'): Conversation
    {
        return Conversation::create([
            'cafe_id' => $cafe->id,
            'guest_token' => (string) Str::uuid(),
            'locale' => $locale,
            'status' => Conversation::STATUS_ACTIVE,
            'last_message_at' => now(),
        ]);
    }
}
