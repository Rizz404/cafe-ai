<?php

namespace App\Http\Middleware;

use App\Models\Cafe;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the published cafe named by the {cafeSlug} route segment and the
 * guest's language, so controllers never look either up themselves.
 *
 * The language is ?lang= on pages and "locale" in JSON bodies; an unsupported
 * value falls back to the cafe's default language.
 */
class ResolveCafe
{
    private const CAFE = 'cafe';

    private const LOCALE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $cafe = Cafe::query()
            ->published()
            ->where('slug', $request->route('cafeSlug'))
            ->firstOrFail();

        $requested = $request->query('lang', $request->input('locale'));

        $locale = in_array($requested, config('cafe.locales'), true) ? $requested : $cafe->default_locale;

        $request->attributes->set(self::CAFE, $cafe);
        $request->attributes->set(self::LOCALE, $locale);

        app()->setLocale($locale);

        return $next($request);
    }

    public static function cafe(Request $request): Cafe
    {
        return $request->attributes->get(self::CAFE);
    }

    public static function locale(Request $request): string
    {
        return $request->attributes->get(self::LOCALE);
    }
}
