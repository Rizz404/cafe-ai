<?php

namespace App\Modules\Experience\Presenters;

use App\Models\Cafe;

/**
 * The main menu of the stage. Sections that live on another page are marked
 * so the stage can fade out, as if the barista were walking the guest over.
 */
final class StageNavigation
{
    /**
     * @param  array<string, string>  $labels
     * @return list<array{key: string, label: string, chip: string, href: string, exit: bool, tour: ?string, topic: string}>
     */
    public static function for(Cafe $cafe, string $locale, array $labels): array
    {
        $tour = StageCopy::narration($locale);
        $url = fn (string $route) => route($route, ['cafeSlug' => $cafe->slug, 'lang' => $locale]);

        return [
            ['key' => 'menu', 'label' => $labels['menu_heading'], 'chip' => $labels['chip_menu'], 'href' => $url('cafe.menu'), 'exit' => true, 'tour' => $tour['tour_menu'], 'topic' => $labels['nav_menu_q']],
            ['key' => 'seating', 'label' => $labels['seating_heading'], 'chip' => $labels['chip_seating'], 'href' => $url('cafe.seating'), 'exit' => true, 'tour' => $tour['tour_seating'], 'topic' => $labels['nav_seating_q']],
            ['key' => 'facilities', 'label' => $labels['facilities_heading'], 'chip' => $labels['chip_facilities'], 'href' => $url('cafe.facilities'), 'exit' => true, 'tour' => $tour['tour_facilities'], 'topic' => $labels['nav_facilities_q']],
            ['key' => 'info', 'label' => $labels['info_heading'], 'chip' => $labels['chip_info'], 'href' => $url('cafe.info'), 'exit' => true, 'tour' => $tour['tour_info'], 'topic' => $labels['nav_info_q']],
            ['key' => 'reservation', 'label' => $labels['reservation_heading'], 'chip' => $labels['chip_reservation'], 'href' => $url('cafe.reservation'), 'exit' => true, 'tour' => $tour['tour_reservation'], 'topic' => $labels['nav_reservation_q']],
            ['key' => 'staff', 'label' => $labels['staff_heading'], 'chip' => $labels['chip_staff'], 'href' => $url('cafe.staff'), 'exit' => true, 'tour' => $tour['tour_staff'], 'topic' => $labels['nav_staff_q']],
        ];
    }
}
