<?php

namespace App\Modules\Operations\Queries;

use App\Models\Cafe;
use App\Models\HandoverRequest;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the cafe team sees first when they sign in: counts of what they manage
 * and the few things waiting for them.
 */
class DashboardMetrics
{
    /**
     * @return array{menu_items: int, seating_areas: int, knowledge_items: int, pending_reservations: int, open_handovers: int}
     */
    public function counts(Cafe $cafe): array
    {
        return [
            'menu_items' => $cafe->menuItems()->count(),
            'seating_areas' => $cafe->seatingAreas()->count(),
            'knowledge_items' => $cafe->knowledgeItems()->count(),
            'pending_reservations' => $cafe->reservations()->where('status', Reservation::STATUS_PENDING)->count(),
            'open_handovers' => $this->openHandovers($cafe)->count(),
        ];
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function recentReservations(Cafe $cafe, int $limit = 5): Collection
    {
        return $cafe->reservations()->latest()->take($limit)->with('seatingArea')->get();
    }

    /**
     * @return Collection<int, HandoverRequest>
     */
    public function latestOpenHandovers(Cafe $cafe, int $limit = 5): Collection
    {
        return $this->openHandovers($cafe)->latest()->take($limit)->with('conversation')->get();
    }

    /**
     * @return Builder<HandoverRequest>
     */
    private function openHandovers(Cafe $cafe): Builder
    {
        return HandoverRequest::whereHas('conversation', fn ($q) => $q->where('cafe_id', $cafe->id))
            ->where('status', HandoverRequest::STATUS_OPEN);
    }
}
