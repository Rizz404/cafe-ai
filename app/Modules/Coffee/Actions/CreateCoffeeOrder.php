<?php

namespace App\Modules\Coffee\Actions;

use App\Models\Cafe;
use App\Models\CoffeeOrder;
use App\Models\MenuItem;
use App\Modules\Coffee\Support\CoffeeMachineSimulator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCoffeeOrder
{
    public function __construct(private readonly CoffeeMachineSimulator $simulator) {}

    public function handle(Cafe $cafe, int $menuItemId, string $sessionHash, string $requestKey, string $locale): CoffeeOrder
    {
        return DB::transaction(function () use ($cafe, $menuItemId, $sessionHash, $requestKey, $locale) {
            // Acquire a write lock before reading the queue, including on SQLite where
            // SELECT FOR UPDATE is a no-op. The cafe's timestamp is left unchanged.
            DB::table('cafes')->where('id', $cafe->id)->update(['updated_at' => DB::raw('updated_at')]);

            $existing = CoffeeOrder::where('cafe_id', $cafe->id)
                ->where('session_hash', $sessionHash)->where('request_key', $requestKey)->first();

            if ($existing) {
                if ($existing->menu_item_id !== $menuItemId) {
                    throw ValidationException::withMessages(['menu_item_id' => __('coffee.changed_request')]);
                }

                return $existing;
            }

            $item = MenuItem::where('cafe_id', $cafe->id)->whereKey($menuItemId)
                ->where('category', MenuItem::CATEGORY_COFFEE)
                ->where('is_active', true)->where('is_sold_out', false)->lockForUpdate()->first();

            if (! $item) {
                throw ValidationException::withMessages(['menu_item_id' => __('coffee.unavailable')]);
            }

            $lastFinish = CoffeeOrder::where('cafe_id', $cafe->id)->max('finishes_at');

            return CoffeeOrder::create([
                'reference' => (string) Str::uuid(),
                'cafe_id' => $cafe->id,
                'menu_item_id' => $item->id,
                'session_hash' => $sessionHash,
                'request_key' => $requestKey,
                'item_name' => $item->translatedName($locale),
                'price' => $item->price,
                'currency' => $cafe->currency,
                'status' => 'queued',
                ...$this->simulator->schedule($lastFinish),
            ]);
        }, attempts: 5);
    }
}
