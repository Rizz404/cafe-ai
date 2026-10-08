<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\HandoverRequest;
use App\Models\Reservation;
use App\Models\TableInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private Cafe $cafe;

    private Cafe $otherCafe;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->cafe = Cafe::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->otherCafe = Cafe::create(['name' => 'Other', 'slug' => 'other', 'public_status' => 'published']);

        $this->staff = User::factory()->create();
        $this->cafe->users()->attach($this->staff->id, ['role' => 'owner', 'status' => 'active']);
    }

    private function foreignReservation(): Reservation
    {
        $area = $this->otherCafe->seatingAreas()->create(['name' => 'Foreign', 'slug' => 'foreign', 'area_type' => 'indoor', 'is_active' => true]);

        return Reservation::create([
            'reference' => 'RS-FOREIGN', 'cafe_id' => $this->otherCafe->id, 'seating_area_id' => $area->id, 'guest_name' => 'Foreign Guest',
            'reservation_date' => now()->addDays(3)->toDateString(), 'time_slot' => '11:00', 'guests' => 2,
        ]);
    }

    private function foreignHandover(): HandoverRequest
    {
        $conversation = Conversation::create(['cafe_id' => $this->otherCafe->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en']);

        return HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => 'complaint', 'summary' => 'Foreign complaint']);
    }

    public function test_visitors_are_sent_to_the_login_page_for_every_admin_screen(): void
    {
        foreach (['admin.dashboard', 'admin.menu-items.index', 'admin.seating-areas.index', 'admin.knowledge-items.index', 'admin.reservations.index', 'admin.handovers.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }
    }

    public function test_staff_only_see_reservations_of_their_own_cafe(): void
    {
        $area = $this->cafe->seatingAreas()->create(['name' => 'Lounge', 'slug' => 'lounge', 'area_type' => 'indoor', 'is_active' => true]);
        Reservation::create([
            'reference' => 'RS-MINE', 'cafe_id' => $this->cafe->id, 'seating_area_id' => $area->id, 'guest_name' => 'My Guest',
            'reservation_date' => now()->addDays(3)->toDateString(), 'time_slot' => '13:00', 'guests' => 2,
        ]);
        $this->foreignReservation();

        $this->actingAs($this->staff)->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee('RS-MINE')
            ->assertDontSee('RS-FOREIGN');
    }

    public function test_staff_cannot_change_another_cafes_reservation(): void
    {
        $reservation = $this->foreignReservation();

        $this->actingAs($this->staff)
            ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
            ->assertNotFound();

        $this->assertSame(Reservation::STATUS_PENDING, $reservation->fresh()->status);
    }

    public function test_cancelling_a_reservation_releases_its_table(): void
    {
        $area = $this->cafe->seatingAreas()->create(['name' => 'Lounge', 'slug' => 'lounge', 'area_type' => 'indoor', 'is_active' => true]);
        $date = now()->addDays(3)->toDateString();
        TableInventory::create(['seating_area_id' => $area->id, 'reservation_date' => $date, 'time_slot' => '13:00', 'total_tables' => 2, 'reserved_tables' => 1]);
        $reservation = Reservation::create([
            'reference' => 'RS-MINE', 'cafe_id' => $this->cafe->id, 'seating_area_id' => $area->id, 'guest_name' => 'My Guest',
            'reservation_date' => $date, 'time_slot' => '13:00', 'guests' => 2,
        ]);

        $this->actingAs($this->staff)
            ->patch(route('admin.reservations.status', $reservation), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame(Reservation::STATUS_CANCELLED, $reservation->fresh()->status);
        $this->assertSame(0, TableInventory::firstOrFail()->reserved_tables);
    }

    public function test_staff_cannot_open_or_answer_another_cafes_handover(): void
    {
        $handover = $this->foreignHandover();

        $this->actingAs($this->staff)->get(route('admin.handovers.show', $handover))->assertNotFound();
        $this->actingAs($this->staff)->post(route('admin.handovers.reply', $handover), ['message' => 'Hi'])->assertNotFound();
        $this->actingAs($this->staff)->post(route('admin.handovers.resolve', $handover))->assertNotFound();
    }

    public function test_resolving_a_handover_hands_the_conversation_back_to_the_barista(): void
    {
        $conversation = Conversation::create(['cafe_id' => $this->cafe->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en', 'status' => Conversation::STATUS_HANDED_OVER]);
        $handover = HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => 'private_event', 'summary' => 'Birthday for 20']);

        $this->actingAs($this->staff)->post(route('admin.handovers.reply', $handover), ['message' => 'Happy to help!'])->assertRedirect();
        $this->assertSame('Happy to help!', ConversationMessage::where('role', ConversationMessage::ROLE_STAFF)->firstOrFail()->content);

        $this->actingAs($this->staff)->post(route('admin.handovers.resolve', $handover))->assertRedirect(route('admin.handovers.index'));

        $this->assertSame(HandoverRequest::STATUS_RESOLVED, $handover->fresh()->status);
        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->fresh()->status);
    }

    public function test_staff_manage_the_menu_of_their_own_cafe_only(): void
    {
        $this->actingAs($this->staff)->post(route('admin.menu-items.store'), [
            'category' => 'coffee', 'name' => 'Flat White', 'price' => 34000, 'tags' => 'Halal, Vegetarian', 'allergens' => 'milk', 'is_active' => '1',
        ])->assertRedirect(route('admin.menu-items.index'));

        $item = $this->cafe->menuItems()->firstOrFail();
        $this->assertSame('flat-white', $item->slug);
        $this->assertSame(['halal', 'vegetarian'], $item->tags);
        $this->assertTrue($item->is_active);

        $foreign = $this->otherCafe->menuItems()->create(['category' => 'coffee', 'name' => 'Foreign', 'slug' => 'foreign', 'price' => 1]);

        $this->actingAs($this->staff)->get(route('admin.menu-items.edit', $foreign))->assertNotFound();
        $this->actingAs($this->staff)->delete(route('admin.menu-items.destroy', $foreign))->assertNotFound();
        $this->assertNotNull($foreign->fresh());
    }

    public function test_seating_area_capacity_is_validated(): void
    {
        $this->actingAs($this->staff)->post(route('admin.seating-areas.store'), [
            'name' => 'Patio', 'area_type' => 'outdoor', 'min_guests' => 6, 'max_guests' => 2, 'reservation_fee' => 0,
        ])->assertSessionHasErrors('max_guests');

        $this->actingAs($this->staff)->post(route('admin.seating-areas.store'), [
            'name' => 'Patio', 'area_type' => 'outdoor', 'min_guests' => 1, 'max_guests' => 6, 'reservation_fee' => 0, 'features' => 'Pet Friendly, wifi', 'is_active' => '1',
        ])->assertRedirect(route('admin.seating-areas.index'));

        $this->assertSame(['pet_friendly', 'wifi'], $this->cafe->seatingAreas()->firstOrFail()->features);
    }

    public function test_the_dashboard_counts_only_the_staffs_own_cafe(): void
    {
        $this->foreignHandover();
        $this->foreignReservation();

        $this->actingAs($this->staff)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['open_handovers'] === 0 && $stats['pending_reservations'] === 0);
    }

    public function test_an_account_without_a_cafe_is_turned_away(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }
}
