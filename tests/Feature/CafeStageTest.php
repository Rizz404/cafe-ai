<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\SeatingArea;
use App\Models\TableInventory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeStageTest extends TestCase
{
    use RefreshDatabase;

    private Cafe $cafe;

    private MenuItem $latte;

    private SeatingArea $terrace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->cafe = Cafe::create([
            'name' => 'Demo Cafe', 'slug' => 'demo', 'public_status' => 'published', 'city' => 'Jakarta', 'country' => 'Indonesia', 'currency' => 'IDR',
            'default_locale' => 'id', 'opening_time' => '08:00', 'closing_time' => '22:00', 'phone' => '+62 21 1234', 'whatsapp' => '6281234567890',
            'description' => 'Kafe hangat di Kemang.',
        ]);

        $this->latte = $this->cafe->menuItems()->create([
            'name' => 'Cafe Latte', 'slug' => 'cafe-latte', 'category' => 'coffee', 'price' => 32000, 'description' => 'Espresso dengan susu.',
            'translations' => ['en' => ['name' => 'Latte', 'description' => 'Espresso with milk.']], 'tags' => ['vegetarian', 'signature'], 'allergens' => ['milk'], 'serving' => 'hot_iced',
            'image_url' => 'images/menu/cafe-latte.svg', 'is_active' => true, 'sort_order' => 0,
        ]);
        $this->cafe->menuItems()->create(['name' => 'Choco Lava', 'slug' => 'choco-lava', 'category' => 'dessert', 'price' => 40000, 'is_sold_out' => true, 'is_active' => true, 'sort_order' => 1]);
        $this->cafe->menuItems()->create(['name' => 'Retired Brew', 'slug' => 'retired', 'category' => 'coffee', 'price' => 1000, 'is_active' => false]);

        $this->terrace = $this->cafe->seatingAreas()->create([
            'name' => 'Garden Terrace', 'slug' => 'garden-terrace', 'area_type' => 'outdoor', 'min_guests' => 1, 'max_guests' => 6, 'features' => ['pet_friendly'],
            'reservation_fee' => 0, 'is_active' => true,
        ]);
        $this->terrace->images()->create(['image_url' => 'images/seating/garden-terrace.svg', 'alt_text' => 'Terrace', 'sort_order' => 0]);
        TableInventory::create([
            'seating_area_id' => $this->terrace->id, 'reservation_date' => CarbonImmutable::now('Asia/Jakarta')->addDays(3)->toDateString(), 'time_slot' => '13:00',
            'total_tables' => 2, 'reserved_tables' => 0,
        ]);

        $this->cafe->knowledgeItems()->create(['category' => 'facilities', 'title' => 'Wi-Fi gratis', 'body' => 'Wi-Fi cepat untuk semua tamu.', 'tags' => ['wifi'], 'is_active' => true]);
        $this->cafe->knowledgeItems()->create(['category' => 'policies', 'title' => 'Kebijakan reservasi', 'body' => 'Batal gratis 2 jam sebelumnya.', 'is_active' => true]);
        $this->cafe->knowledgeItems()->create(['category' => 'faq', 'title' => 'Ada musala?', 'body' => 'Ada, di sebelah toilet.', 'is_active' => true]);
    }

    public function test_the_opening_screen_lists_published_cafes(): void
    {
        Cafe::create(['name' => 'Draft Cafe', 'slug' => 'draft']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Demo Cafe')
            ->assertDontSee('Draft Cafe')
            ->assertSee(route('cafe.menu', ['cafeSlug' => 'demo', 'lang' => 'id']), false);
    }

    public function test_the_counter_greets_guests_in_the_requested_language(): void
    {
        $this->get('/demo')->assertOk()->assertSee('Demo Cafe')->assertSee('Selamat datang di');
        $this->get('/demo?lang=en')->assertOk()->assertSee('Welcome to')->assertSee('lang="en"', false);
        $this->get('/demo?lang=ja')->assertOk()->assertSee('ようこそ');
        $this->get('/demo?lang=xx')->assertOk()->assertSee('Selamat datang di');
    }

    public function test_every_scene_renders_and_carries_the_barista_chat(): void
    {
        $routes = [
            'cafe.show', 'cafe.menu', 'cafe.seating', 'cafe.facilities', 'cafe.info', 'cafe.staff', 'cafe.reservation',
        ];

        foreach ($routes as $route) {
            $this->get(route($route, ['cafeSlug' => 'demo']))
                ->assertOk()
                ->assertSee('id="barista-app"', false)
                ->assertSee('data-scene=', false);
        }
    }

    public function test_unpublished_and_unknown_cafes_are_not_found(): void
    {
        Cafe::create(['name' => 'Draft', 'slug' => 'draft']);

        $this->get('/draft')->assertNotFound();
        $this->get('/draft/menu')->assertNotFound();
        $this->get('/nowhere')->assertNotFound();
    }

    public function test_the_menu_scene_narrates_from_stored_data_and_lists_active_items(): void
    {
        $this->get('/demo/menu?lang=en')
            ->assertOk()
            ->assertSee('We have 2 items, from IDR 32.000')
            ->assertSee('Latte')
            ->assertSee('Choco Lava')
            ->assertDontSee('Retired Brew');
    }

    public function test_a_menu_item_shows_its_price_allergens_and_tags(): void
    {
        $this->get('/demo/menu/cafe-latte?lang=en')
            ->assertOk()
            ->assertSee('Latte')
            ->assertSee('IDR 32.000')
            ->assertSee('Espresso with milk.')
            ->assertSee('Milk')
            ->assertSee('Signature')
            ->assertSee('Contains: milk.')
            ->assertSee('images/menu/cafe-latte.svg', false);

        $this->get('/demo/menu/choco-lava?lang=en')->assertOk()->assertSee('Sold out today');

        $this->get('/demo/menu/retired')->assertNotFound();
        $this->get('/demo/menu/nothing-here')->assertNotFound();
    }

    public function test_the_seating_scenes_show_capacity_and_fees(): void
    {
        $this->get('/demo/seating?lang=en')->assertOk()->assertSee('Garden Terrace')->assertSee('Here is our seating area, Garden Terrace.');

        $this->get('/demo/seating/garden-terrace?lang=en')
            ->assertOk()
            ->assertSee('Garden Terrace')
            ->assertSee('Pet friendly')
            ->assertSee('There is no reservation fee.')
            ->assertSee('area=garden-terrace', false);

        $this->get('/demo/seating/nowhere')->assertNotFound();
    }

    public function test_the_facility_scenes_show_stored_services(): void
    {
        $facility = $this->cafe->knowledgeItems()->where('category', 'facilities')->firstOrFail();

        $this->get('/demo/facilities')->assertOk()->assertSee('Wi-Fi gratis')->assertDontSee('Kebijakan reservasi');
        $this->get('/demo/facilities/'.$facility->id)->assertOk()->assertSee('Wi-Fi cepat untuk semua tamu.');
        $this->get('/demo/facilities/99999')->assertNotFound();
    }

    public function test_the_info_scene_shows_opening_hours_policies_and_faq(): void
    {
        $this->get('/demo/info?lang=en')
            ->assertOk()
            ->assertSee('08:00')
            ->assertSee('22:00')
            ->assertSee('We are open from 08:00 to 22:00.')
            ->assertSee('Kebijakan reservasi')
            ->assertSee('Ada musala?')
            ->assertSee('Kafe hangat di Kemang.');
    }

    public function test_the_staff_scene_offers_whatsapp_and_phone(): void
    {
        $this->get('/demo/staff')
            ->assertOk()
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('tel:+62211234', false);
    }

    public function test_the_reservation_scene_offers_slots_and_seating_areas(): void
    {
        $this->get('/demo/reservation?area=garden-terrace&lang=en')
            ->assertOk()
            ->assertSee('data-wizard', false)
            ->assertSee('data-preselect-area="garden-terrace"', false)
            ->assertSee('13:00–15:00')
            ->assertSee('Garden Terrace');
    }
}
