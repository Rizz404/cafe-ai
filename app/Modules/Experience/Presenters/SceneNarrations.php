<?php

namespace App\Modules\Experience\Presenters;

use App\Models\Cafe;
use App\Models\CafeKnowledgeItem;
use App\Models\MenuItem;
use App\Models\SeatingArea;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * What the AI Barista says when the guest steps into a scene. Every sentence
 * is built from stored cafe data, never generated.
 */
final class SceneNarrations
{
    /**
     * @param  Collection<int, MenuItem>  $items
     * @return array{menu: ?string, item: array<string, string>}
     */
    public static function menu(Cafe $cafe, Collection $items, string $locale): array
    {
        $templates = StageCopy::narration($locale);
        $terms = StageCopy::terms($locale);
        $money = self::money($cafe);
        $sentence = self::sentence($locale);
        $separator = $locale === 'ja' ? '、' : ', ';

        if ($items->isEmpty()) {
            return ['menu' => null, 'item' => []];
        }

        $intro = str_replace(
            [':count', ':price'],
            [(string) $items->count(), $money($items->min('price'))],
            $templates[$items->count() === 1 ? 'menu_one' : 'menu']
        );

        $narrations = $items->mapWithKeys(function (MenuItem $item) use ($templates, $terms, $money, $sentence, $separator, $locale) {
            $parts = [$item->translatedName($locale).'.', filled($item->translatedDescription($locale)) ? $sentence(trim($item->translatedDescription($locale))) : null];

            if ($item->serving) {
                $parts[] = str_replace(':serving', $terms['serving'][$item->serving] ?? Str::headline($item->serving), $templates['item_serving']);
            }

            if (! empty($item->allergens)) {
                $list = collect($item->allergens)->map(fn ($allergen) => $terms['allergen'][$allergen] ?? $allergen)->implode($separator);
                $parts[] = str_replace(':list', $list, $templates['item_allergens']);
            }

            $parts[] = str_replace(':price', $money($item->price), $templates['item_price']);

            if ($item->is_sold_out) {
                $parts[] = $templates['item_sold_out'];
            }

            return [$item->slug => implode(' ', array_filter($parts))];
        })->all();

        return ['menu' => $intro, 'item' => $narrations];
    }

    /**
     * @param  Collection<int, SeatingArea>  $areas
     * @return array{seating: ?string, area: array<string, string>}
     */
    public static function seating(Cafe $cafe, Collection $areas, string $locale): array
    {
        $templates = StageCopy::narration($locale);
        $money = self::money($cafe);
        $sentence = self::sentence($locale);

        if ($areas->isEmpty()) {
            return ['seating' => null, 'area' => []];
        }

        $first = $areas->first()->translatedName($locale);
        $intro = str_replace(
            [':count', ':first'],
            [(string) $areas->count(), $first],
            $templates[$areas->count() === 1 ? 'seating_one' : 'seating']
        );

        $narrations = $areas->mapWithKeys(function (SeatingArea $area) use ($templates, $money, $sentence, $locale) {
            $parts = [$area->translatedName($locale).'.', filled($area->translatedDescription($locale)) ? $sentence(trim($area->translatedDescription($locale))) : null];

            $parts[] = $area->min_guests > 1
                ? str_replace([':min', ':max'], [(string) $area->min_guests, (string) $area->max_guests], $templates['seat_guests'])
                : str_replace(':max', (string) $area->max_guests, $templates['seat_guests_one']);

            $parts[] = (float) $area->reservation_fee > 0
                ? str_replace(':fee', $money($area->reservation_fee), $templates['seat_fee'])
                : $templates['seat_free'];

            if ($area->minimum_spend !== null && (float) $area->minimum_spend > 0) {
                $parts[] = str_replace(':spend', $money($area->minimum_spend), $templates['seat_spend']);
            }

            return [$area->slug => implode(' ', array_filter($parts))];
        })->all();

        return ['seating' => $intro, 'area' => $narrations];
    }

    /**
     * Intros for the facilities list and the cafe information scene, built
     * only from stored cafe data.
     *
     * @param  Collection<int, CafeKnowledgeItem>  $facilities
     * @return array{facilities: ?string, info: string}
     */
    public static function scenes(Cafe $cafe, Collection $facilities, string $locale): array
    {
        $templates = StageCopy::narration($locale);
        $separator = $locale === 'ja' ? '、' : '; ';
        $time = fn (?string $value) => $value ? substr($value, 0, 5) : null;

        $intro = null;

        if ($facilities->isNotEmpty()) {
            $examples = $facilities->take(3)->map(fn ($item) => $item->translatedTitle($locale))->implode($separator);
            $intro = str_replace(
                [':count', ':examples'],
                [(string) $facilities->count(), $examples],
                $templates[$facilities->count() === 1 ? 'facilities_one' : 'facilities']
            );
        }

        $location = collect([$cafe->city, $cafe->country])->filter()->implode($locale === 'ja' ? '、' : ', ');
        $parts = [str_replace(':cafe', $cafe->name, $templates['info_welcome'])];

        if ($location !== '') {
            $parts[] = str_replace(':location', $location, $templates['info_place']);
        }

        if ($time($cafe->opening_time) && $time($cafe->closing_time)) {
            $parts[] = str_replace([':open', ':close'], [$time($cafe->opening_time), $time($cafe->closing_time)], $templates['info_hours']);
        }

        $parts[] = $templates['info_more'];

        return ['facilities' => $intro, 'info' => implode(' ', $parts)];
    }

    /**
     * @return \Closure(mixed): string
     */
    private static function money(Cafe $cafe): \Closure
    {
        return fn ($value) => $cafe->currency.' '.number_format((float) $value, 0, ',', '.');
    }

    /**
     * @return \Closure(string): string
     */
    private static function sentence(string $locale): \Closure
    {
        return fn (string $text) => preg_match('/[.!?。！？]$/u', $text) ? $text : $text.($locale === 'ja' ? '。' : '.');
    }
}
