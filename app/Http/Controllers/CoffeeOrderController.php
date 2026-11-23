<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveCafe;
use App\Models\CoffeeOrder;
use App\Models\MenuItem;
use App\Modules\Coffee\Actions\CreateCoffeeOrder;
use App\Modules\Coffee\Support\CoffeeMachineSimulator;
use App\Modules\Experience\Queries\BuildStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CoffeeOrderController extends Controller
{
    public function create(Request $request, BuildStage $stage): View
    {
        $data = $request->validate(['menu_item_id' => ['required', 'integer', 'min:1']]);
        $cafe = ResolveCafe::cafe($request);
        $item = MenuItem::where('cafe_id', $cafe->id)->whereKey($data['menu_item_id'])
            ->where('category', MenuItem::CATEGORY_COFFEE)
            ->where('is_active', true)->where('is_sold_out', false)->firstOrFail();

        return view('cafe.coffee-order', [
            ...$stage->handle($cafe, ResolveCafe::locale($request), 'menu'),
            'menuItem' => $item,
            'requestKey' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, CreateCoffeeOrder $create, CoffeeMachineSimulator $simulator): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'menu_item_id' => ['required', 'integer', 'min:1'],
            'request_key' => ['required', 'uuid'],
        ]);
        $cafe = ResolveCafe::cafe($request);
        $order = $create->handle($cafe, (int) $data['menu_item_id'], $this->sessionHash($request), $data['request_key'], ResolveCafe::locale($request));
        $request->session()->put('coffee_orders.'.$cafe->id, $order->reference);
        $url = route('coffee-orders.show', ['cafeSlug' => $cafe->slug, 'reference' => $order->reference, 'lang' => ResolveCafe::locale($request)]);

        return $request->expectsJson()
            ? response()->json([...$simulator->snapshot($order), 'tracking_url' => $url], $order->wasRecentlyCreated ? 201 : 200)
            : redirect()->to($url, 303);
    }

    public function show(Request $request, string $cafeSlug, string $reference, BuildStage $stage, CoffeeMachineSimulator $simulator): View
    {
        $order = $this->ownedOrder($request, $reference);

        return view('cafe.coffee-order', [
            ...$stage->handle(ResolveCafe::cafe($request), ResolveCafe::locale($request), 'menu'),
            'order' => $order,
            'snapshot' => $simulator->snapshot($order),
        ]);
    }

    public function status(Request $request, string $cafeSlug, string $reference, CoffeeMachineSimulator $simulator): JsonResponse
    {
        return response()->json($simulator->snapshot($this->ownedOrder($request, $reference)))
            ->header('Cache-Control', 'private, no-store');
    }

    private function ownedOrder(Request $request, string $reference): CoffeeOrder
    {
        return CoffeeOrder::where('cafe_id', ResolveCafe::cafe($request)->id)
            ->where('session_hash', $this->sessionHash($request))
            ->where('reference', $reference)->firstOrFail();
    }

    private function sessionHash(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }
}
