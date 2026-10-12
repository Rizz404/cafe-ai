<?php

namespace App\Modules\Conversation\Support;

use App\Models\ConversationMessage;

/**
 * A conversation message as the guest's browser receives it.
 */
final class MessagePresenter
{
    /**
     * @return array{id: int, role: string, content: ?string, ui_payload: ?array<int, array<string, mixed>>, suggested_actions: list<array{action: string, item?: string, area?: string}>, created_at: string}
     */
    public static function format(ConversationMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'ui_payload' => $message->ui_payload,
            'suggested_actions' => self::suggestedActions($message->ui_payload),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Interface actions that follow from what the barista just showed, so the
     * guest can step into the matching scene instead of typing again. Booking
     * is not one of those scenes: it continues in the chat itself.
     *
     * @param  list<array<string, mixed>>|null  $uiPayload
     * @return list<array{action: string, item?: string, area?: string}>
     */
    public static function suggestedActions(?array $uiPayload): array
    {
        $actions = [];

        foreach ($uiPayload ?? [] as $payload) {
            $type = $payload['type'] ?? null;
            $item = $payload['item']['menu_item_slug'] ?? null;

            if ($type === 'menu_detail' && $item) {
                $actions[] = ['action' => 'view_item', 'item' => $item];
                $actions[] = ['action' => 'reserve'];
            } elseif ($type === 'handover') {
                $actions[] = ['action' => 'staff'];
            }
        }

        return collect($actions)->unique(fn (array $action) => $action['action'].($action['item'] ?? '').($action['area'] ?? ''))->values()->all();
    }
}
