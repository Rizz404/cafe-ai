<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveCafe;
use App\Models\Cafe;
use App\Models\CafeKnowledgeItem;
use App\Models\MenuItem;
use App\Models\SeatingArea;
use App\Modules\Experience\Presenters\StageBackdrop;
use App\Modules\Experience\Presenters\StageCopy;
use App\Modules\Experience\Queries\BuildStage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The guest-facing stage: one page per scene, all built from the same shell
 * data. The cafe and the language are resolved by the ResolveCafe middleware.
 */
class CafePageController extends Controller
{
    public function __construct(private readonly BuildStage $stage) {}

    public function index(Request $request): View
    {
        $cafes = Cafe::published()->orderBy('name')->get();

        $locale = in_array($request->query('lang'), config('cafe.locales'), true)
            ? $request->query('lang')
            : ($cafes->first()?->default_locale ?? config('cafe.default_locale'));

        return view('opening', [
            'cafes' => $cafes,
            'locale' => $locale,
            'supportedLocales' => config('cafe.locales'),
            'opening' => StageCopy::opening($locale),
            'avatarImage' => StageBackdrop::avatar(),
            'backdrop' => StageBackdrop::for('home'),
        ]);
    }

    public function show(Request $request): View
    {
        return view('cafe.show', $this->stageData($request, 'home'));
    }

    public function menu(Request $request): View
    {
        return view('cafe.menu-index', $this->stageData($request, 'menu'));
    }

    public function menuItem(Request $request, string $cafeSlug, string $itemSlug): View
    {
        $data = $this->stageData($request, 'menu-item');
        $items = $data['menuItems'];
        $index = $items->search(fn (MenuItem $item) => $item->slug === $itemSlug);

        abort_if($index === false, 404);

        return view('cafe.menu-detail', [
            ...$data,
            'menuItem' => $items[$index],
            'itemIndex' => $index,
            ...$this->neighbours($items, $index, 'Item'),
        ]);
    }

    public function seating(Request $request): View
    {
        return view('cafe.seating-index', $this->stageData($request, 'seating'));
    }

    public function seatingArea(Request $request, string $cafeSlug, string $areaSlug): View
    {
        $data = $this->stageData($request, 'seating-area');
        $areas = $data['seatingAreas'];
        $index = $areas->search(fn (SeatingArea $area) => $area->slug === $areaSlug);

        abort_if($index === false, 404);

        return view('cafe.seating-detail', [
            ...$data,
            'seatingArea' => $areas[$index],
            'areaIndex' => $index,
            ...$this->neighbours($areas, $index, 'Area'),
        ]);
    }

    public function facilities(Request $request): View
    {
        return view('cafe.facilities-index', $this->stageData($request, 'facilities'));
    }

    public function facility(Request $request, string $cafeSlug, int $facilityId): View
    {
        $data = $this->stageData($request, 'facility');
        $facilities = $data['facilities'];
        $index = $facilities->search(fn (CafeKnowledgeItem $item) => $item->id === $facilityId);

        abort_if($index === false, 404);

        return view('cafe.facility-detail', [
            ...$data,
            'facility' => $facilities[$index],
            'facilityIndex' => $index,
            ...$this->neighbours($facilities, $index, 'Facility'),
        ]);
    }

    public function info(Request $request): View
    {
        return view('cafe.info-index', $this->stageData($request, 'info'));
    }

    public function staff(Request $request): View
    {
        return view('cafe.staff-index', $this->stageData($request, 'staff'));
    }

    public function reservationScene(Request $request): View
    {
        return view('cafe.reservation-index', [
            ...$this->stageData($request, 'reservation'),
            'preselectedArea' => (string) $request->query('area', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stageData(Request $request, string $scene): array
    {
        return $this->stage->handle(ResolveCafe::cafe($request), ResolveCafe::locale($request), $scene);
    }

    /**
     * The previous and next entry around an index, wrapping at both ends.
     *
     * @param  Collection<int, mixed>  $items
     * @return array<string, mixed>
     */
    private function neighbours(Collection $items, int $index, string $suffix): array
    {
        return [
            'previous'.$suffix => $items[($index - 1 + $items->count()) % $items->count()],
            'next'.$suffix => $items[($index + 1) % $items->count()],
        ];
    }
}
