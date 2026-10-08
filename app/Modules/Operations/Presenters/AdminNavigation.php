<?php

namespace App\Modules\Operations\Presenters;

/**
 * The admin menu: where each entry leads and what it is called.
 */
final class AdminNavigation
{
    /**
     * @return list<array{route: string, label: string}>
     */
    public static function items(): array
    {
        return [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
            ['route' => 'admin.menu-items.index', 'label' => 'Menu'],
            ['route' => 'admin.seating-areas.index', 'label' => 'Seating areas'],
            ['route' => 'admin.knowledge-items.index', 'label' => 'Knowledge base'],
            ['route' => 'admin.reservations.index', 'label' => 'Reservations'],
            ['route' => 'admin.handovers.index', 'label' => 'Handovers'],
        ];
    }
}
