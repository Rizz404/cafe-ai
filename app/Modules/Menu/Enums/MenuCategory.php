<?php

namespace App\Modules\Menu\Enums;

/**
 * The kinds of drink and dish a cafe menu is grouped into.
 */
enum MenuCategory: string
{
    case Coffee = 'coffee';
    case NonCoffee = 'non_coffee';
    case Tea = 'tea';
    case Food = 'food';
    case Snack = 'snack';
    case Dessert = 'dessert';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
