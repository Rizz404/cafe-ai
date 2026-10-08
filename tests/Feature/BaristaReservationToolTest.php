<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Models\Reservation;
use App\Models\TableInventory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\RunsBaristaTools;
use Tests\TestCase;

class BaristaReservationToolTest extends TestCase
{
    use RefreshDatabase, RunsBaristaTools;

    public function test_ai_reservation_tool_creates_a_reference_and_holds_one_table(): void
    {
        $cafe = Cafe::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'currency' => 'IDR']);
        $area = $cafe->seatingAreas()->create(['name' => 'Private Room', 'slug' => 'private-room', 'area_type' => 'private', 'min_guests' => 6, 'max_guests' => 12, 'reservation_fee' => 150000, 'is_active' => true]);
        $conversation = Conversation::create(['cafe_id' => $cafe->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en']);

        $date = CarbonImmutable::now($cafe->timezone)->addDays(5);
        foreach (['11:00', '13:00'] as $slot) {
            TableInventory::create(['seating_area_id' => $area->id, 'reservation_date' => $date->toDateString(), 'time_slot' => $slot, 'total_tables' => 1, 'reserved_tables' => 0]);
        }

        $input = [
            'seating_area_slug' => 'private-room', 'date' => $date->toDateString(), 'time_slot' => '11:00',
            'guests' => 8, 'guest_name' => 'Ayu', 'guest_phone' => '+62 811 111 222',
        ];

        $quote = $this->runTool($cafe, $conversation, 'check_table_availability', $input);
        $this->assertTrue($quote['ui']['available']);
        $this->assertSame(150000.0, $quote['ui']['quote']['reservation_fee']);

        $result = $this->runTool($cafe, $conversation, 'create_reservation_request', $input);

        $reservation = Reservation::firstOrFail();
        $this->assertSame($reservation->reference, $result['ui']['reservation']['reservation_reference']);
        $this->assertSame(8, $reservation->guests);
        $this->assertSame('11:00', $reservation->time_slot);
        $this->assertSame(150000.0, (float) $reservation->deposit_total);
        $this->assertSame([1, 0], TableInventory::orderBy('time_slot')->pluck('reserved_tables')->all());

        $taken = $this->runTool($cafe, $conversation, 'create_reservation_request', $input);
        $this->assertNull($taken['ui']);
        $this->assertSame(1, Reservation::count());

        $full = $this->runTool($cafe, $conversation, 'check_table_availability', $input);
        $this->assertFalse($full['ui']['available']);
        $this->assertStringContainsString('13:00', $full['text']);
    }

    public function test_the_tools_refuse_a_party_that_does_not_fit_and_a_slot_that_does_not_exist(): void
    {
        $cafe = Cafe::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $area = $cafe->seatingAreas()->create(['name' => 'Bar', 'slug' => 'bar', 'area_type' => 'bar', 'min_guests' => 1, 'max_guests' => 2, 'is_active' => true]);
        $conversation = Conversation::create(['cafe_id' => $cafe->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en']);
        $date = CarbonImmutable::now($cafe->timezone)->addDays(2);
        TableInventory::create(['seating_area_id' => $area->id, 'reservation_date' => $date->toDateString(), 'time_slot' => '15:00', 'total_tables' => 2, 'reserved_tables' => 0]);

        $base = ['seating_area_slug' => 'bar', 'date' => $date->toDateString(), 'guest_name' => 'Ayu', 'guest_phone' => '+62 811 111 222'];

        $tooMany = $this->runTool($cafe, $conversation, 'create_reservation_request', [...$base, 'time_slot' => '15:00', 'guests' => 5]);
        $this->assertNull($tooMany['ui']);

        $unknownSlot = $this->runTool($cafe, $conversation, 'check_table_availability', [...$base, 'time_slot' => '03:00', 'guests' => 2]);
        $this->assertStringContainsString('15:00', $unknownSlot['text']);

        $past = $this->runTool($cafe, $conversation, 'check_table_availability', [...$base, 'date' => '2001-01-01', 'time_slot' => '15:00', 'guests' => 2]);
        $this->assertStringContainsString('past', $past['text']);

        $this->assertSame(0, Reservation::count());
    }

    public function test_menu_search_filters_by_diet_and_budget_and_hides_unpublished_items(): void
    {
        $cafe = Cafe::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'currency' => 'IDR']);
        $cafe->menuItems()->create(['name' => 'Aglio Olio', 'slug' => 'aglio-olio', 'category' => 'food', 'price' => 48000, 'tags' => ['vegan'], 'is_active' => true]);
        $cafe->menuItems()->create(['name' => 'Club Sandwich', 'slug' => 'club-sandwich', 'category' => 'food', 'price' => 52000, 'tags' => ['halal'], 'is_active' => true]);
        $cafe->menuItems()->create(['name' => 'Secret Dish', 'slug' => 'secret', 'category' => 'food', 'price' => 10000, 'tags' => ['vegan'], 'is_active' => false]);
        $conversation = Conversation::create(['cafe_id' => $cafe->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en']);


        $vegan = $this->runTool($cafe, $conversation, 'search_menu', ['dietary' => 'vegan']);
        $this->assertSame(['aglio-olio'], collect($vegan['ui']['items'])->pluck('menu_item_slug')->all());

        $cheap = $this->runTool($cafe, $conversation, 'search_menu', ['category' => 'food', 'max_price' => 50000]);
        $this->assertSame(['aglio-olio'], collect($cheap['ui']['items'])->pluck('menu_item_slug')->all());

        $none = $this->runTool($cafe, $conversation, 'search_menu', ['query' => 'sushi']);
        $this->assertNull($none['ui']);
    }
}
