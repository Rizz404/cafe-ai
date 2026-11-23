<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\CoffeeOrder;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoffeeOrderTest extends TestCase
{
    use RefreshDatabase;

    private Cafe $cafe;

    private MenuItem $coffee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withCookie(config('session.cookie'), Str::random(40))->withCredentials();

        $this->cafe = Cafe::factory()->create(['slug' => 'demo', 'public_status' => 'published', 'currency' => 'IDR', 'default_locale' => 'id']);
        $this->coffee = MenuItem::factory()->create([
            'cafe_id' => $this->cafe->id, 'category' => MenuItem::CATEGORY_COFFEE,
            'name' => 'Espresso', 'price' => 25000,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return ['menu_item_id' => $this->coffee->id, 'request_key' => (string) Str::uuid(), ...$overrides];
    }

    public function test_confirmation_uses_the_existing_menu_and_creates_one_cup_at_the_server_price(): void
    {
        $this->get('/demo/menu/'.$this->coffee->slug)->assertSee(__('coffee.title'));
        $this->get('/demo/orders/create?menu_item_id='.$this->coffee->id)
            ->assertSee('Konfirmasi pesanan')->assertSee('Simulasi mesin kopi')->assertSee('Espresso');

        $response = $this->postJson('/demo/orders', $this->payload(['price' => 1, 'quantity' => 50, 'status' => 'ready']))
            ->assertCreated()->assertJsonPath('price', '25000.00')
            ->assertJsonPath('status', 'queued')->assertJsonPath('simulated', true);

        $this->assertDatabaseHas('coffee_orders', [
            'reference' => $response->json('reference'), 'cafe_id' => $this->cafe->id,
            'menu_item_id' => $this->coffee->id, 'item_name' => 'Espresso', 'price' => 25000, 'currency' => 'IDR',
        ]);
        $this->assertDatabaseCount('coffee_orders', 1);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_replaying_a_confirmation_returns_the_same_order_without_extending_the_queue(): void
    {
        $payload = $this->payload();
        $first = $this->postJson('/demo/orders', $payload)->assertCreated();
        $order = CoffeeOrder::firstOrFail();
        // A successful order remains recoverable if the menu becomes unavailable on retry.
        $this->coffee->update(['is_sold_out' => true]);

        $this->postJson('/demo/orders', $payload)->assertOk()
            ->assertJsonPath('reference', $first->json('reference'))
            ->assertJsonPath('finishes_at', $first->json('finishes_at'));

        $this->assertDatabaseCount('coffee_orders', 1);
        $this->assertSame($order->starts_at->toIso8601String(), $order->fresh()->starts_at->toIso8601String());
    }

    public function test_two_orders_run_in_sequence_and_polling_or_reloading_does_not_restart_them(): void
    {
        $this->freezeTime();
        $first = $this->postJson('/demo/orders', $this->payload())->assertCreated();
        $second = $this->postJson('/demo/orders', $this->payload())->assertCreated()->assertJsonPath('queue_position', 2);
        $firstStatus = '/demo/orders/'.$first->json('reference').'/status';
        $secondStatus = '/demo/orders/'.$second->json('reference').'/status';
        $orders = CoffeeOrder::orderBy('id')->get();
        $this->assertSame($orders[0]->finishes_at->toIso8601String(), $orders[1]->starts_at->toIso8601String());
        $this->assertSame(30, (int) $orders[1]->starts_at->diffInSeconds($orders[1]->finishes_at));

        $this->travelTo($orders[0]->starts_at->addSeconds(15));
        $this->getJson($firstStatus)->assertOk()->assertJsonPath('status', 'brewing')->assertJsonPath('progress', 50);
        $this->get('/demo/orders/'.$first->json('reference'))->assertSee('COF-')->assertSee('Membuat minuman');
        $this->getJson($firstStatus)->assertJsonPath('progress', 50);
        $this->getJson($secondStatus)->assertJsonPath('status', 'queued')->assertJsonPath('progress', 0);

        $this->travelTo($orders[0]->finishes_at);
        $this->getJson($firstStatus)->assertJsonPath('status', 'ready')->assertJsonPath('progress', 100);
        $this->getJson($secondStatus)->assertJsonPath('status', 'brewing')->assertJsonPath('progress', 0);

        $this->travelTo($orders[1]->finishes_at->addSeconds(5));
        $this->getJson($secondStatus)->assertJsonPath('status', 'ready')->assertJsonPath('progress', 100);
        $this->assertDatabaseHas('coffee_orders', ['id' => $orders[1]->id, 'status' => 'ready']);
        $this->assertSame($orders[0]->starts_at->toIso8601String(), $orders[0]->fresh()->starts_at->toIso8601String());
    }

    public function test_other_cafes_and_sessions_cannot_access_an_order_or_its_status(): void
    {
        $response = $this->postJson('/demo/orders', $this->payload())->assertCreated();
        $reference = $response->json('reference');
        Cafe::factory()->create(['slug' => 'other', 'public_status' => 'published']);

        $this->get('/other/orders/'.$reference)->assertNotFound();
        $this->getJson('/other/orders/'.$reference.'/status')->assertNotFound();

        $this->withCookie(config('session.cookie'), Str::random(40));
        $this->get('/demo/orders/'.$reference)->assertNotFound();
        $this->getJson('/demo/orders/'.$reference.'/status')->assertNotFound();
    }

    public function test_different_cafes_have_independent_machine_queues(): void
    {
        $this->freezeTime();
        $this->postJson('/demo/orders', $this->payload())->assertCreated();
        $this->postJson('/demo/orders', $this->payload())->assertCreated();
        $otherCafe = Cafe::factory()->create(['slug' => 'other', 'public_status' => 'published']);
        $otherCoffee = MenuItem::factory()->create(['cafe_id' => $otherCafe->id, 'category' => MenuItem::CATEGORY_COFFEE]);

        $response = $this->postJson('/other/orders', $this->payload(['menu_item_id' => $otherCoffee->id]))
            ->assertCreated()->assertJsonPath('queue_position', 1);

        $first = CoffeeOrder::where('cafe_id', $this->cafe->id)->orderBy('id')->firstOrFail();
        $this->assertSame($first->starts_at->toIso8601String(), $response->json('starts_at'));
    }

    public function test_unavailable_non_coffee_deleted_and_cross_cafe_items_are_rejected(): void
    {
        $items = [
            MenuItem::factory()->soldOut()->create(['cafe_id' => $this->cafe->id, 'category' => MenuItem::CATEGORY_COFFEE]),
            MenuItem::factory()->inactive()->create(['cafe_id' => $this->cafe->id, 'category' => MenuItem::CATEGORY_COFFEE]),
            MenuItem::factory()->create(['cafe_id' => $this->cafe->id, 'category' => MenuItem::CATEGORY_FOOD]),
            MenuItem::factory()->create(['category' => MenuItem::CATEGORY_COFFEE]),
        ];
        $this->coffee->delete();
        $items[] = $this->coffee;

        foreach ($items as $item) {
            $this->get('/demo/orders/create?menu_item_id='.$item->id)->assertNotFound();
            $this->postJson('/demo/orders', $this->payload(['menu_item_id' => $item->id]))
                ->assertUnprocessable()->assertJsonValidationErrors('menu_item_id');
        }

        $this->assertDatabaseCount('coffee_orders', 0);
    }

    public function test_a_confirmation_key_cannot_be_reused_for_another_drink(): void
    {
        $payload = $this->payload();
        $this->postJson('/demo/orders', $payload)->assertCreated();
        $other = MenuItem::factory()->create(['cafe_id' => $this->cafe->id, 'category' => MenuItem::CATEGORY_COFFEE]);

        $this->postJson('/demo/orders', [...$payload, 'menu_item_id' => $other->id])
            ->assertUnprocessable()->assertJsonValidationErrors('menu_item_id');

        $this->assertDatabaseCount('coffee_orders', 1);
    }

    public function test_browser_confirmation_redirects_to_tracking_and_status_is_not_cacheable(): void
    {
        $this->get('/demo/orders/create?menu_item_id='.$this->coffee->id);

        $response = $this->post('/demo/orders?lang=id', $this->payload());
        $order = CoffeeOrder::firstOrFail();
        $response->assertRedirect(route('coffee-orders.show', ['cafeSlug' => 'demo', 'reference' => $order->reference, 'lang' => 'id']));
        $this->get('/demo/orders/'.$order->reference)->assertSee('Simulasi mesin kopi')->assertSee('COF-');
        $this->getJson('/demo/orders/'.$order->reference.'/status')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonMissingPath('session_hash')->assertJsonMissingPath('request_key');
        $this->get('/demo/menu')->assertSee('Lihat pesanan terakhir');
    }

    public function test_invalid_payloads_do_not_create_orders(): void
    {
        $this->postJson('/demo/orders', [])->assertUnprocessable()->assertJsonValidationErrors(['menu_item_id', 'request_key']);
        $this->postJson('/demo/orders', ['menu_item_id' => 'invalid', 'request_key' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['menu_item_id', 'request_key']);
        $this->assertDatabaseCount('coffee_orders', 0);
    }
}
