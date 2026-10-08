<?php

namespace App\Modules\Conversation\Actions;

use App\Models\Cafe;
use App\Models\Conversation;

/**
 * Remembers where in the UI the guest is, so the barista can answer for "this
 * drink" or the reservation they are filling in. Only values that exist for
 * this cafe are kept; nothing personal is stored here.
 */
class RememberUiContext
{
    /**
     * @param  array{scene?: ?string, selected_menu_item?: ?string, selected_seating_area?: ?string, selected_facility?: ?int, reservation?: ?array<string, mixed>}  $context
     */
    public function handle(Cafe $cafe, Conversation $conversation, array $context): void
    {
        $updates = [];

        if (in_array($context['scene'] ?? null, Conversation::SCENES, true)) {
            $updates['current_scene'] = $context['scene'];
        }

        if (filled($context['selected_menu_item'] ?? null)) {
            $menuItem = $cafe->menuItems()->where('is_active', true)->where('slug', $context['selected_menu_item'])->first();

            if ($menuItem) {
                $updates['selected_menu_item_id'] = $menuItem->id;
            }
        }

        if (filled($context['selected_seating_area'] ?? null)) {
            $area = $cafe->seatingAreas()->where('is_active', true)->where('slug', $context['selected_seating_area'])->first();

            if ($area) {
                $updates['selected_seating_area_id'] = $area->id;
            }
        }

        if (filled($context['selected_facility'] ?? null)) {
            $facility = $cafe->knowledgeItems()->where('is_active', true)->whereKey($context['selected_facility'])->first();

            if ($facility) {
                $updates['selected_facility_id'] = $facility->id;
            }
        }

        if (array_key_exists('reservation', $context)) {
            $updates['reservation_state'] = $context['reservation'] ?: null;
        }

        if ($updates !== []) {
            $conversation->update($updates);
        }
    }
}
