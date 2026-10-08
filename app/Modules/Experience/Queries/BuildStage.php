<?php

namespace App\Modules\Experience\Queries;

use App\Models\Cafe;
use App\Models\CafeKnowledgeItem;
use App\Modules\Experience\Presenters\SceneNarrations;
use App\Modules\Experience\Presenters\StageBackdrop;
use App\Modules\Experience\Presenters\StageCopy;
use App\Modules\Experience\Presenters\StageNavigation;
use App\Modules\Reservation\Support\ReservationHandover;
use App\Modules\Reservation\Support\TableAvailability;

/**
 * Everything the shared stage shell needs, for whichever scene the guest has
 * walked into.
 */
class BuildStage
{
    public function __construct(
        private readonly ReservationHandover $handover,
        private readonly TableAvailability $availability,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Cafe $cafe, string $locale, string $scene): array
    {
        $menuItems = $cafe->menuItems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->values();

        $seatingAreas = $cafe->seatingAreas()
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->values();

        $facilities = $cafe->knowledgeItems()->where('is_active', true)
            ->whereIn('category', [CafeKnowledgeItem::CATEGORY_FACILITIES, CafeKnowledgeItem::CATEGORY_EVENTS, CafeKnowledgeItem::CATEGORY_LOCATION])
            ->orderBy('sort_order')->get()->values();

        $labels = StageCopy::labels($locale);

        return [
            'cafe' => $cafe,
            'menuItems' => $menuItems,
            'seatingAreas' => $seatingAreas,
            'locale' => $locale,
            'scene' => $scene,
            'backdrop' => StageBackdrop::for($scene),
            'navItems' => StageNavigation::for($cafe, $locale, $labels),
            'supportedLocales' => config('cafe.locales'),
            'labels' => $labels,
            'stageCopy' => StageCopy::copy($locale),
            'terms' => StageCopy::terms($locale),
            'wizard' => StageCopy::wizard($locale),
            'narration' => StageCopy::narration($locale),
            'menuNarrations' => SceneNarrations::menu($cafe, $menuItems, $locale),
            'seatingNarrations' => SceneNarrations::seating($cafe, $seatingAreas, $locale),
            'staffLinks' => $this->handover->forStaff($cafe, $locale),
            'today' => now($cafe->timezone)->toDateString(),
            'maxAdvanceDays' => TableAvailability::MAX_ADVANCE_DAYS,
            'slots' => collect($this->availability->slotsFor($cafe))
                ->map(fn (string $slot) => ['value' => $slot, 'label' => TableAvailability::slotRange($slot)])
                ->all(),
            'infoItems' => $cafe->knowledgeItems()->where('is_active', true)
                ->whereIn('category', [CafeKnowledgeItem::CATEGORY_GENERAL, CafeKnowledgeItem::CATEGORY_POLICIES, CafeKnowledgeItem::CATEGORY_FAQ])
                ->orderBy('sort_order')->get()->groupBy('category'),
            'facilities' => $facilities,
            'sceneNarrations' => SceneNarrations::scenes($cafe, $facilities, $locale),
        ];
    }
}
