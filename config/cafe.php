<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cafe experience
    |--------------------------------------------------------------------------
    |
    | Guest-facing settings shared by every cafe. Per-cafe values (name,
    | currency, opening hours, default locale) live in the cafes table.
    | Provider connection values stay in config/services.php under
    | "local_llm"; AI budgets are in config/assistant.php.
    |
    */

    'locales' => ['id', 'en', 'ja'],

    'default_locale' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Stage artwork
    |--------------------------------------------------------------------------
    |
    | The small round picture of the barista used beside chat and narration,
    | and the artwork per scene: a plain illustrated background plus one
    | cut-out of the barista layered in front, so she can stand clear of the
    | panels. Scenes without artwork of their own borrow another scene's.
    |
    */

    'avatar_image' => 'images/barista/avatar.svg',

    'scenes' => [
        'home' => ['image' => 'images/scenes/counter.svg', 'character' => 'images/character/barista-greeting.svg', 'focus' => 'center 40%', 'focusMobile' => '50% 30%', 'anchor' => 'left', 'text' => 'top'],
        'menu' => ['image' => 'images/scenes/menu.svg', 'character' => 'images/character/barista-greeting.svg', 'focus' => 'center 40%', 'focusMobile' => '50% 30%', 'anchor' => 'left', 'text' => 'top'],
        'seating' => ['image' => 'images/scenes/seating.svg', 'character' => 'images/character/barista-greeting.svg', 'focus' => 'center 45%', 'focusMobile' => '60% 30%', 'anchor' => 'left', 'text' => 'top'],
        'facilities' => ['image' => 'images/scenes/terrace.svg', 'character' => 'images/character/barista-greeting.svg', 'focus' => 'center 45%', 'focusMobile' => '30% 30%', 'anchor' => 'left', 'text' => 'bottom'],
        'info' => ['image' => 'images/scenes/window.svg', 'character' => 'images/character/barista-greeting.svg', 'focus' => 'center 45%', 'focusMobile' => '70% 30%', 'anchor' => 'left', 'text' => 'bottom'],
        'staff' => ['image' => 'images/scenes/backbar.svg', 'character' => 'images/character/barista-clasped.svg', 'focus' => 'center 40%', 'focusMobile' => '60% 30%', 'anchor' => 'left', 'text' => 'top'],
        'reservation' => ['image' => 'images/scenes/reservation.svg', 'character' => 'images/character/barista-notepad.svg', 'focus' => 'center 45%', 'focusMobile' => '30% 30%', 'anchor' => 'left', 'text' => 'bottom'],
    ],

    'scene_artwork' => [
        'menu-item' => 'menu',
        'seating-area' => 'seating',
        'facility' => 'facilities',
    ],

];
