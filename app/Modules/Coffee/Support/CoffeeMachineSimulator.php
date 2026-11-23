<?php

namespace App\Modules\Coffee\Support;

use App\Models\CoffeeOrder;
use Carbon\CarbonImmutable;

class CoffeeMachineSimulator
{
    public const BREW_SECONDS = 30;

    /** @return array{starts_at: CarbonImmutable, finishes_at: CarbonImmutable} */
    public function schedule(?string $lastFinish): array
    {
        // A short dispatch interval lets the guest see the queued stage, even when idle.
        $start = CarbonImmutable::now()->addSeconds(3)->startOfSecond();

        if ($lastFinish !== null && CarbonImmutable::parse($lastFinish)->greaterThan($start)) {
            $start = CarbonImmutable::parse($lastFinish);
        }

        return ['starts_at' => $start, 'finishes_at' => $start->addSeconds(self::BREW_SECONDS)];
    }

    /** @return array<string, mixed> */
    public function snapshot(CoffeeOrder $order): array
    {
        $now = CarbonImmutable::now();
        $status = $now->greaterThanOrEqualTo($order->finishes_at)
            ? 'ready'
            : ($now->greaterThanOrEqualTo($order->starts_at) ? 'brewing' : 'queued');

        // Conditional transitions prevent overlapping polls from moving a status backwards.
        if ($status !== 'queued') {
            CoffeeOrder::whereKey($order->id)
                ->whereIn('status', $status === 'ready' ? ['queued', 'brewing'] : ['queued'])
                ->update(['status' => $status]);
        }

        $progress = (int) max(0, min(100, floor(
            ($now->getTimestamp() - $order->starts_at->getTimestamp()) / self::BREW_SECONDS * 100
        )));

        return [
            'reference' => $order->reference,
            'number' => 'COF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'item_name' => $order->item_name,
            'price' => $order->price,
            'currency' => $order->currency,
            'status' => $status,
            'progress' => $progress,
            'queue_position' => $status === 'queued' ? CoffeeOrder::where('cafe_id', $order->cafe_id)
                ->where('finishes_at', '>', $now)
                ->where('finishes_at', '<=', $order->starts_at)->count() + 1 : 0,
            'starts_at' => $order->starts_at->toIso8601String(),
            'finishes_at' => $order->finishes_at->toIso8601String(),
            'simulated' => true,
        ];
    }
}
