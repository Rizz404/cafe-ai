<?php

namespace App\Console\Commands;

use App\Models\SeatingArea;
use App\Modules\Reservation\Support\OpenTableInventory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cafe:open-inventory {--cafe= : Optional cafe slug}')]
#[Description('Open table slots up to the booking window, preserving existing capacity and reservations')]
class OpenInventoryCommand extends Command
{
    public function handle(OpenTableInventory $open): int
    {
        $slug = $this->option('cafe');
        $count = 0;

        SeatingArea::query()->where('is_active', true)
            ->whereHas('cafe', fn ($query) => $query->when($slug, fn ($query) => $query->where('slug', $slug)))
            ->with('cafe')
            ->chunkById(50, function ($areas) use ($open, &$count) {
                foreach ($areas as $area) {
                    $count += $open->handle($area);
                }
            });

        $this->info('Opened '.$count.' new table slots. Existing capacity and reservations were preserved.');

        return self::SUCCESS;
    }
}
