<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\SeatingArea;
use App\Models\TableInventory;
use App\Modules\Reservation\Support\TableAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenInventoryCommandTest extends TestCase
{
    use RefreshDatabase;

    private SeatingArea $lounge;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $cafe = Cafe::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'currency' => 'IDR', 'default_locale' => 'en']);
        $this->lounge = $cafe->seatingAreas()->create(['name' => 'Indoor Lounge', 'slug' => 'indoor-lounge', 'area_type' => 'indoor', 'min_guests' => 1, 'max_guests' => 4, 'reservation_fee' => 0, 'is_active' => true]);
        $this->today = CarbonImmutable::now($cafe->timezone)->startOfDay();
    }

    private function slot(string $date, string $time, int $total, int $reserved = 0): void
    {
        TableInventory::create(['seating_area_id' => $this->lounge->id, 'reservation_date' => $date, 'time_slot' => $time, 'total_tables' => $total, 'reserved_tables' => $reserved]);
    }

    public function test_it_extends_the_window_with_the_latest_day_slots_and_keeps_reservations(): void
    {
        $lastDay = $this->today->addDays(10)->toDateString();
        $this->slot($this->today->addDays(2)->toDateString(), '11:00', 6, 4);
        $this->slot($lastDay, '11:00', 6, 2);
        $this->slot($lastDay, '13:00', 5, 5);

        $this->artisan('cafe:open-inventory')->assertSuccessful();

        $horizon = $this->today->addDays(TableAvailability::MAX_ADVANCE_DAYS)->toDateString();
        $this->assertSame($horizon, CarbonImmutable::parse(TableInventory::query()->max('reservation_date'))->toDateString());

        $added = TableInventory::query()->whereDate('reservation_date', $horizon)->orderBy('time_slot')->get();
        $this->assertSame(['11:00', '13:00'], $added->pluck('time_slot')->all());
        $this->assertSame([6, 5], $added->pluck('total_tables')->all());
        $this->assertSame([0, 0], $added->pluck('reserved_tables')->all());

        $this->assertSame(4, TableInventory::query()->whereDate('reservation_date', $this->today->addDays(2)->toDateString())->value('reserved_tables'));
        $this->assertSame(5, TableInventory::query()->whereDate('reservation_date', $lastDay)->where('time_slot', '13:00')->value('reserved_tables'));
    }

    public function test_running_it_twice_adds_nothing_the_second_time(): void
    {
        $this->slot($this->today->toDateString(), '11:00', 3);

        $this->artisan('cafe:open-inventory')->assertSuccessful();
        $count = TableInventory::count();

        $this->artisan('cafe:open-inventory')->expectsOutputToContain('Opened 0 new table slots')->assertSuccessful();
        $this->assertSame($count, TableInventory::count());
        $this->assertSame(TableAvailability::MAX_ADVANCE_DAYS + 1, $count);
    }

    public function test_areas_without_inventory_and_inactive_areas_are_left_alone(): void
    {
        $this->lounge->update(['is_active' => false]);
        $this->slot($this->today->toDateString(), '11:00', 3);

        $this->artisan('cafe:open-inventory')->assertSuccessful();

        $this->assertSame(1, TableInventory::count());
    }

    public function test_it_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command, 'cafe:open-inventory'));

        $this->assertCount(1, $events);
        $this->assertSame('0 1 * * *', $events->first()->expression);
    }
}
