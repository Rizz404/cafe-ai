<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\CafeKnowledgeItem;
use App\Models\MenuItem;
use App\Models\SeatingArea;
use App\Models\TableInventory;
use App\Models\User;
use App\Modules\Reservation\Support\TableAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCafeSeeder extends Seeder
{
    /**
     * Every sitting is two hours, so these are the slots the demo cafe sells.
     *
     * @var list<string>
     */
    private const SLOTS = ['09:00', '11:00', '13:00', '15:00', '17:00', '19:00'];

    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@cafeai.test'],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $cafe = Cafe::firstOrCreate(
            ['name' => 'Cafe AI'],
            [
                'slug' => Cafe::generateUniqueSlug('Cafe AI'),
                'description' => 'Kafe hangat di Kemang, Jakarta, dengan kopi single origin, dapur rumahan, dan teras taman yang teduh.',
                'translations' => [
                    'id' => ['description' => 'Kafe hangat di Kemang, Jakarta, dengan kopi single origin, dapur rumahan, dan teras taman yang teduh.'],
                    'en' => ['description' => 'A warm cafe in Kemang, Jakarta, with single-origin coffee, a homestyle kitchen and a shady garden terrace.'],
                    'ja' => ['description' => 'ジャカルタ・クマンにある温かなカフェ。シングルオリジンのコーヒー、家庭的なキッチン、木陰のガーデンテラスが自慢です。'],
                ],
                'address' => 'Jl. Kemang Raya No. 21, Kemang',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.2615,
                'longitude' => 106.8130,
                'phone' => '+62 21 7123 4567',
                'whatsapp' => '6281234567890',
                'email' => 'hello@cafeai.test',
                'instagram' => '@cafeai.demo',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'default_locale' => 'id',
                'opening_time' => '08:00',
                'closing_time' => '22:00',
                'public_status' => 'published',
            ]
        );

        $cafe->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner', 'status' => 'active'],
        ]);

        $this->seedMenu($cafe);
        $this->seedSeating($cafe);
        $this->seedKnowledgeBase($cafe);

        $this->command?->info('Demo login: owner@cafeai.test — password: password');
    }

    private function seedMenu(Cafe $cafe): void
    {
        $sort = 0;

        foreach ($this->menuDefinitions() as $definition) {
            $cafe->menuItems()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'image_url' => 'images/menu/'.$definition['slug'].'.svg', 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    private function seedSeating(Cafe $cafe): void
    {
        $sort = 0;

        foreach ($this->seatingDefinitions() as $definition) {
            $images = $definition['images'];
            $totalTables = $definition['total_tables'];
            unset($definition['images'], $definition['total_tables']);

            $area = $cafe->seatingAreas()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'sort_order' => $sort++, 'is_active' => true]
            );

            $imageSort = 0;
            foreach ($images as $image) {
                $area->images()->updateOrCreate(
                    ['image_url' => $image['url']],
                    ['tags' => $image['tags'], 'alt_text' => $image['alt'], 'sort_order' => $imageSort++]
                );
            }

            $this->seedInventory($area, $totalTables);
        }
    }

    /**
     * Opens every slot for the booking window, with a deterministic spread of
     * already-reserved tables so some slots read as busy or full.
     */
    private function seedInventory(SeatingArea $area, int $totalTables): void
    {
        $today = CarbonImmutable::now($area->cafe->timezone)->startOfDay();
        $rows = [];

        for ($day = 0; $day <= TableAvailability::MAX_ADVANCE_DAYS; $day++) {
            $date = $today->addDays($day)->toDateString();

            foreach (self::SLOTS as $slot) {
                $roll = crc32($area->slug.$date.$slot) % 100;

                $rows[] = [
                    'seating_area_id' => $area->id,
                    'reservation_date' => $date,
                    'time_slot' => $slot,
                    'total_tables' => $totalTables,
                    'reserved_tables' => (int) floor((($roll / 100) ** 2) * ($totalTables + 0.99)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            TableInventory::upsert($chunk, ['seating_area_id', 'reservation_date', 'time_slot'], ['total_tables']);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function menuDefinitions(): array
    {
        return [
            [
                'slug' => 'espresso', 'category' => 'coffee', 'name' => 'Espresso', 'price' => 22000, 'serving' => 'hot', 'calories' => 5,
                'tags' => ['halal', 'vegan', 'dairy_free', 'caffeine'], 'allergens' => [], 'is_featured' => false,
                'description' => 'Double shot dari biji single origin Gayo, pekat dengan crema tebal.',
                'translations' => [
                    'id' => ['name' => 'Espresso', 'description' => 'Double shot dari biji single origin Gayo, pekat dengan crema tebal.'],
                    'en' => ['name' => 'Espresso', 'description' => 'A double shot of single-origin Gayo beans, bold with thick crema.'],
                    'ja' => ['name' => 'エスプレッソ', 'description' => 'ガヨ産シングルオリジンのダブルショット。濃厚でクレマたっぷりです。'],
                ],
            ],
            [
                'slug' => 'americano', 'category' => 'coffee', 'name' => 'Americano', 'price' => 25000, 'serving' => 'hot_iced', 'calories' => 10,
                'tags' => ['halal', 'vegan', 'dairy_free'], 'allergens' => [], 'is_featured' => false,
                'description' => 'Espresso yang dilarutkan dengan air, ringan dan bersih. Tersedia panas atau dingin.',
                'translations' => [
                    'id' => ['name' => 'Americano', 'description' => 'Espresso yang dilarutkan dengan air, ringan dan bersih. Tersedia panas atau dingin.'],
                    'en' => ['name' => 'Americano', 'description' => 'Espresso lengthened with water, light and clean. Served hot or iced.'],
                    'ja' => ['name' => 'アメリカーノ', 'description' => 'エスプレッソをお湯で割った、すっきり軽やかな一杯。ホットでもアイスでも。'],
                ],
            ],
            [
                'slug' => 'cafe-latte', 'category' => 'coffee', 'name' => 'Cafe Latte', 'price' => 32000, 'serving' => 'hot_iced', 'calories' => 180,
                'tags' => ['halal', 'vegetarian', 'bestseller'], 'allergens' => ['milk'], 'is_featured' => true,
                'description' => 'Espresso dengan susu steam yang lembut dan latte art buatan barista kami.',
                'translations' => [
                    'id' => ['name' => 'Cafe Latte', 'description' => 'Espresso dengan susu steam yang lembut dan latte art buatan barista kami.'],
                    'en' => ['name' => 'Cafe Latte', 'description' => 'Espresso with silky steamed milk and latte art by our baristas.'],
                    'ja' => ['name' => 'カフェラテ', 'description' => 'エスプレッソになめらかなスチームミルクを合わせ、バリスタがラテアートを描きます。'],
                ],
            ],
            [
                'slug' => 'cappuccino', 'category' => 'coffee', 'name' => 'Cappuccino', 'price' => 32000, 'serving' => 'hot', 'calories' => 150,
                'tags' => ['halal', 'vegetarian'], 'allergens' => ['milk'], 'is_featured' => false,
                'description' => 'Espresso, susu, dan foam tebal dengan taburan cokelat tipis di atasnya.',
                'translations' => [
                    'id' => ['name' => 'Cappuccino', 'description' => 'Espresso, susu, dan foam tebal dengan taburan cokelat tipis di atasnya.'],
                    'en' => ['name' => 'Cappuccino', 'description' => 'Espresso, milk and thick foam with a light dusting of cocoa on top.'],
                    'ja' => ['name' => 'カプチーノ', 'description' => 'エスプレッソとミルク、厚めのフォームに、ほんのりココアをふりかけて。'],
                ],
            ],
            [
                'slug' => 'aren-latte', 'category' => 'coffee', 'name' => 'Es Kopi Susu Aren', 'price' => 30000, 'serving' => 'iced', 'calories' => 210,
                'tags' => ['halal', 'vegetarian', 'signature', 'bestseller'], 'allergens' => ['milk'], 'is_featured' => true,
                'description' => 'Signature kami: espresso, susu segar, dan gula aren asli yang karamelnya wangi.',
                'translations' => [
                    'id' => ['name' => 'Es Kopi Susu Aren', 'description' => 'Signature kami: espresso, susu segar, dan gula aren asli yang karamelnya wangi.'],
                    'en' => ['name' => 'Iced Palm Sugar Latte', 'description' => 'Our signature: espresso, fresh milk and real palm sugar with a fragrant caramel note.'],
                    'ja' => ['name' => 'アイス・パームシュガーラテ', 'description' => '当店の看板メニュー。エスプレッソと新鮮なミルク、香ばしいキャラメル風味の本物のパームシュガー。'],
                ],
            ],
            [
                'slug' => 'matcha-latte', 'category' => 'non_coffee', 'name' => 'Matcha Latte', 'price' => 38000, 'serving' => 'hot_iced', 'calories' => 190,
                'tags' => ['halal', 'vegetarian'], 'allergens' => ['milk'], 'is_featured' => true,
                'description' => 'Matcha Uji yang dikocok manual, dipadu susu segar. Manis secukupnya.',
                'translations' => [
                    'id' => ['name' => 'Matcha Latte', 'description' => 'Matcha Uji yang dikocok manual, dipadu susu segar. Manis secukupnya.'],
                    'en' => ['name' => 'Matcha Latte', 'description' => 'Hand-whisked Uji matcha with fresh milk, lightly sweetened.'],
                    'ja' => ['name' => '抹茶ラテ', 'description' => '宇治抹茶を手で点て、新鮮なミルクと合わせました。甘さは控えめです。'],
                ],
            ],
            [
                'slug' => 'hot-chocolate', 'category' => 'non_coffee', 'name' => 'Hot Chocolate', 'price' => 34000, 'serving' => 'hot', 'calories' => 260,
                'tags' => ['halal', 'vegetarian', 'caffeine_free'], 'allergens' => ['milk'], 'is_featured' => false,
                'description' => 'Cokelat Belgia leleh dengan susu hangat dan whipped cream.',
                'translations' => [
                    'id' => ['name' => 'Hot Chocolate', 'description' => 'Cokelat Belgia leleh dengan susu hangat dan whipped cream.'],
                    'en' => ['name' => 'Hot Chocolate', 'description' => 'Melted Belgian chocolate with warm milk and whipped cream.'],
                    'ja' => ['name' => 'ホットチョコレート', 'description' => 'とろけるベルギーチョコレートに温かいミルクとホイップクリームを添えて。'],
                ],
            ],
            [
                'slug' => 'iced-lemon-tea', 'category' => 'tea', 'name' => 'Es Teh Lemon', 'price' => 24000, 'serving' => 'iced', 'calories' => 90,
                'tags' => ['halal', 'vegan', 'dairy_free'], 'allergens' => [], 'is_featured' => false,
                'description' => 'Teh hitam seduh dingin dengan perasan lemon dan irisan lemon segar.',
                'translations' => [
                    'id' => ['name' => 'Es Teh Lemon', 'description' => 'Teh hitam seduh dingin dengan perasan lemon dan irisan lemon segar.'],
                    'en' => ['name' => 'Iced Lemon Tea', 'description' => 'Cold-brewed black tea with fresh lemon juice and a lemon slice.'],
                    'ja' => ['name' => 'アイスレモンティー', 'description' => '水出しの紅茶に、搾りたてのレモン果汁とレモンスライスを添えて。'],
                ],
            ],
            [
                'slug' => 'jasmine-tea', 'category' => 'tea', 'name' => 'Jasmine Tea', 'price' => 22000, 'serving' => 'hot', 'calories' => 0,
                'tags' => ['halal', 'vegan', 'dairy_free', 'caffeine_free'], 'allergens' => [], 'is_featured' => false,
                'description' => 'Teh melati harum yang disajikan dalam teko kecil untuk berdua.',
                'translations' => [
                    'id' => ['name' => 'Teh Melati', 'description' => 'Teh melati harum yang disajikan dalam teko kecil untuk berdua.'],
                    'en' => ['name' => 'Jasmine Tea', 'description' => 'Fragrant jasmine tea served in a small pot, enough for two.'],
                    'ja' => ['name' => 'ジャスミンティー', 'description' => '香り高いジャスミンティーを、2人分の小さなポットでご提供します。'],
                ],
            ],
            [
                'slug' => 'nasi-goreng-kemangi', 'category' => 'food', 'name' => 'Nasi Goreng Kemangi', 'price' => 45000, 'serving' => 'single', 'calories' => 620,
                'tags' => ['halal', 'spicy', 'bestseller'], 'allergens' => ['egg', 'soy', 'seafood'], 'is_featured' => true,
                'description' => 'Nasi goreng wangi kemangi dengan telur mata sapi, acar, dan kerupuk udang.',
                'translations' => [
                    'id' => ['name' => 'Nasi Goreng Kemangi', 'description' => 'Nasi goreng wangi kemangi dengan telur mata sapi, acar, dan kerupuk udang.'],
                    'en' => ['name' => 'Basil Fried Rice', 'description' => 'Basil-scented fried rice with a fried egg, pickles and prawn crackers.'],
                    'ja' => ['name' => 'バジルのナシゴレン', 'description' => 'バジルが香るインドネシア風チャーハンに、目玉焼き、ピクルス、エビせんべいを添えて。'],
                ],
            ],
            [
                'slug' => 'club-sandwich', 'category' => 'food', 'name' => 'Club Sandwich', 'price' => 52000, 'serving' => 'single', 'calories' => 680,
                'tags' => ['halal'], 'allergens' => ['gluten', 'egg', 'milk'], 'is_featured' => false,
                'description' => 'Roti panggang berlapis ayam, telur, selada, tomat, dan keju, dengan kentang goreng.',
                'translations' => [
                    'id' => ['name' => 'Club Sandwich', 'description' => 'Roti panggang berlapis ayam, telur, selada, tomat, dan keju, dengan kentang goreng.'],
                    'en' => ['name' => 'Club Sandwich', 'description' => 'Toasted bread layered with chicken, egg, lettuce, tomato and cheese, with fries.'],
                    'ja' => ['name' => 'クラブサンドイッチ', 'description' => 'チキン、卵、レタス、トマト、チーズを重ねたトーストサンド。フライドポテト付き。'],
                ],
            ],
            [
                'slug' => 'aglio-olio', 'category' => 'food', 'name' => 'Spaghetti Aglio Olio', 'price' => 48000, 'serving' => 'single', 'calories' => 540,
                'tags' => ['halal', 'vegan', 'dairy_free', 'spicy'], 'allergens' => ['gluten'], 'is_featured' => false,
                'description' => 'Spaghetti dengan bawang putih, cabai kering, dan minyak zaitun. Tanpa produk hewani.',
                'translations' => [
                    'id' => ['name' => 'Spaghetti Aglio Olio', 'description' => 'Spaghetti dengan bawang putih, cabai kering, dan minyak zaitun. Tanpa produk hewani.'],
                    'en' => ['name' => 'Spaghetti Aglio Olio', 'description' => 'Spaghetti with garlic, dried chilli and olive oil. No animal products.'],
                    'ja' => ['name' => 'スパゲッティ アーリオ・オーリオ', 'description' => 'ニンニク、唐辛子、オリーブオイルのシンプルなパスタ。動物性食材は使用していません。'],
                ],
            ],
            [
                'slug' => 'truffle-fries', 'category' => 'snack', 'name' => 'Truffle Fries', 'price' => 38000, 'serving' => 'shareable', 'calories' => 450,
                'tags' => ['halal', 'vegetarian'], 'allergens' => ['milk'], 'is_featured' => false,
                'description' => 'Kentang goreng renyah dengan minyak truffle, parmesan, dan saus aioli.',
                'translations' => [
                    'id' => ['name' => 'Truffle Fries', 'description' => 'Kentang goreng renyah dengan minyak truffle, parmesan, dan saus aioli.'],
                    'en' => ['name' => 'Truffle Fries', 'description' => 'Crispy fries with truffle oil, parmesan and aioli.'],
                    'ja' => ['name' => 'トリュフフライ', 'description' => 'トリュフオイルとパルメザン、アイオリソースをかけたカリカリのポテト。'],
                ],
            ],
            [
                'slug' => 'butter-croissant', 'category' => 'snack', 'name' => 'Butter Croissant', 'price' => 28000, 'serving' => 'single', 'calories' => 310,
                'tags' => ['halal', 'vegetarian'], 'allergens' => ['gluten', 'milk', 'egg'], 'is_featured' => true,
                'description' => 'Croissant berlapis yang dipanggang setiap pagi, renyah di luar dan lembut di dalam.',
                'translations' => [
                    'id' => ['name' => 'Butter Croissant', 'description' => 'Croissant berlapis yang dipanggang setiap pagi, renyah di luar dan lembut di dalam.'],
                    'en' => ['name' => 'Butter Croissant', 'description' => 'Layered croissant baked every morning, crisp outside and soft inside.'],
                    'ja' => ['name' => 'バタークロワッサン', 'description' => '毎朝焼き上げる層の美しいクロワッサン。外はサクッと、中はふんわり。'],
                ],
            ],
            [
                'slug' => 'tiramisu', 'category' => 'dessert', 'name' => 'Tiramisu', 'price' => 42000, 'serving' => 'single', 'calories' => 380,
                'tags' => ['vegetarian'], 'allergens' => ['gluten', 'milk', 'egg'], 'is_featured' => true,
                'description' => 'Lapisan biskuit kopi dan krim mascarpone, ditaburi cokelat bubuk. Buatan sendiri setiap hari.',
                'translations' => [
                    'id' => ['name' => 'Tiramisu', 'description' => 'Lapisan biskuit kopi dan krim mascarpone, ditaburi cokelat bubuk. Buatan sendiri setiap hari.'],
                    'en' => ['name' => 'Tiramisu', 'description' => 'Layers of coffee-soaked sponge and mascarpone cream dusted with cocoa, made in-house daily.'],
                    'ja' => ['name' => 'ティラミス', 'description' => 'コーヒーを染み込ませたスポンジとマスカルポーネクリームを重ね、ココアをふりかけた自家製デザート。'],
                ],
            ],
            [
                'slug' => 'choco-lava-cake', 'category' => 'dessert', 'name' => 'Choco Lava Cake', 'price' => 40000, 'serving' => 'single', 'calories' => 430,
                'tags' => ['vegetarian'], 'allergens' => ['gluten', 'milk', 'egg'], 'is_featured' => false, 'is_sold_out' => true,
                'description' => 'Kue cokelat hangat dengan isian lava yang meleleh, disajikan dengan es krim vanila.',
                'translations' => [
                    'id' => ['name' => 'Choco Lava Cake', 'description' => 'Kue cokelat hangat dengan isian lava yang meleleh, disajikan dengan es krim vanila.'],
                    'en' => ['name' => 'Choco Lava Cake', 'description' => 'Warm chocolate cake with a molten centre, served with vanilla ice cream.'],
                    'ja' => ['name' => 'チョコレートラバケーキ', 'description' => '中からとろりとチョコレートが溢れる温かいケーキ。バニラアイス添え。'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seatingDefinitions(): array
    {
        return [
            [
                'slug' => 'indoor-lounge', 'name' => 'Indoor Lounge', 'area_type' => 'indoor', 'min_guests' => 1, 'max_guests' => 4,
                'features' => ['wifi', 'power_outlet', 'air_conditioning', 'sofa'], 'reservation_fee' => 0, 'minimum_spend' => null, 'total_tables' => 6,
                'description' => 'Ruang ber-AC dengan sofa empuk dan colokan di setiap meja, nyaman untuk ngobrol atau bekerja.',
                'translations' => [
                    'id' => ['name' => 'Lounge Dalam', 'description' => 'Ruang ber-AC dengan sofa empuk dan colokan di setiap meja, nyaman untuk ngobrol atau bekerja.'],
                    'en' => ['name' => 'Indoor Lounge', 'description' => 'An air-conditioned room with soft sofas and a power outlet at every table, good for catching up or working.'],
                    'ja' => ['name' => 'インドアラウンジ', 'description' => '空調の効いた室内に、ふかふかのソファと各テーブルの電源。おしゃべりにも作業にも。'],
                ],
                'images' => [
                    ['url' => 'images/seating/indoor-lounge.svg', 'tags' => ['sofa'], 'alt' => 'Sofa dan meja di Indoor Lounge'],
                    ['url' => 'images/scenes/seating.svg', 'tags' => ['room'], 'alt' => 'Suasana Indoor Lounge'],
                ],
            ],
            [
                'slug' => 'garden-terrace', 'name' => 'Garden Terrace', 'area_type' => 'outdoor', 'min_guests' => 1, 'max_guests' => 6,
                'features' => ['garden_view', 'pet_friendly', 'smoking_area', 'wifi'], 'reservation_fee' => 0, 'minimum_spend' => null, 'total_tables' => 5,
                'description' => 'Teras taman teduh dengan lampu gantung hangat di malam hari. Boleh membawa hewan peliharaan.',
                'translations' => [
                    'id' => ['name' => 'Teras Taman', 'description' => 'Teras taman teduh dengan lampu gantung hangat di malam hari. Boleh membawa hewan peliharaan.'],
                    'en' => ['name' => 'Garden Terrace', 'description' => 'A shady garden terrace with warm string lights in the evening. Pets are welcome.'],
                    'ja' => ['name' => 'ガーデンテラス', 'description' => '木陰のガーデンテラス。夜は温かな電球が灯ります。ペット同伴もOKです。'],
                ],
                'images' => [
                    ['url' => 'images/seating/garden-terrace.svg', 'tags' => ['garden'], 'alt' => 'Meja di Garden Terrace'],
                    ['url' => 'images/scenes/terrace.svg', 'tags' => ['terrace'], 'alt' => 'Suasana Garden Terrace di sore hari'],
                ],
            ],
            [
                'slug' => 'bar-counter', 'name' => 'Bar Counter', 'area_type' => 'bar', 'min_guests' => 1, 'max_guests' => 2, 'features' => ['power_outlet', 'wifi', 'quiet'],
                'reservation_fee' => 0, 'minimum_spend' => null, 'total_tables' => 4,
                'description' => 'Kursi tinggi menghadap mesin espresso, pas untuk melihat barista bekerja atau menikmati kopi sendirian.',
                'translations' => [
                    'id' => ['name' => 'Meja Bar', 'description' => 'Kursi tinggi menghadap mesin espresso, pas untuk melihat barista bekerja atau menikmati kopi sendirian.'],
                    'en' => ['name' => 'Bar Counter', 'description' => 'High stools facing the espresso machine, ideal for watching the baristas or enjoying a coffee on your own.'],
                    'ja' => ['name' => 'カウンター席', 'description' => 'エスプレッソマシンを望むハイスツール。バリスタの手元を眺めながら、ひとりの時間にぴったりです。'],
                ],
                'images' => [
                    ['url' => 'images/seating/bar-counter.svg', 'tags' => ['bar'], 'alt' => 'Kursi tinggi di Bar Counter'],
                    ['url' => 'images/scenes/counter.svg', 'tags' => ['counter'], 'alt' => 'Bar Counter dengan mesin espresso'],
                ],
            ],
            [
                'slug' => 'private-room', 'name' => 'Private Meeting Room', 'area_type' => 'private', 'min_guests' => 6, 'max_guests' => 12,
                'features' => ['air_conditioning', 'projector', 'wifi', 'quiet'], 'reservation_fee' => 150000, 'minimum_spend' => 600000, 'total_tables' => 1,
                'description' => 'Ruang privat untuk rapat kecil, arisan, atau ulang tahun, lengkap dengan proyektor dan layanan antar ke meja.',
                'translations' => [
                    'id' => ['name' => 'Ruang Meeting Privat', 'description' => 'Ruang privat untuk rapat kecil, arisan, atau ulang tahun, lengkap dengan proyektor dan layanan antar ke meja.'],
                    'en' => ['name' => 'Private Meeting Room', 'description' => 'A private room for small meetings, gatherings or birthdays, with a projector and table service.'],
                    'ja' => ['name' => 'プライベートルーム', 'description' => '小規模なミーティングや集まり、誕生日にご利用いただける個室。プロジェクターとテーブルサービス付きです。'],
                ],
                'images' => [
                    ['url' => 'images/seating/private-room.svg', 'tags' => ['meeting'], 'alt' => 'Meja panjang di Private Meeting Room'],
                    ['url' => 'images/scenes/reservation.svg', 'tags' => ['table'], 'alt' => 'Meja yang sudah direservasi'],
                ],
            ],
        ];
    }

    private function seedKnowledgeBase(Cafe $cafe): void
    {
        $sort = 0;

        foreach ($this->knowledgeDefinitions() as $definition) {
            $cafe->knowledgeItems()->updateOrCreate(
                ['title' => $definition['title']],
                [...$definition, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function knowledgeDefinitions(): array
    {
        return [
            [
                'category' => CafeKnowledgeItem::CATEGORY_GENERAL, 'title' => 'Tentang Cafe AI', 'tags' => ['about', 'cerita', 'story'], 'image_url' => null,
                'body' => 'Cafe AI adalah kafe di Kemang yang menyajikan kopi single origin Nusantara, dapur rumahan, dan roti yang dipanggang setiap pagi. Kami buka setiap hari pukul 08.00 sampai 22.00.',
                'translations' => [
                    'en' => ['title' => 'About Cafe AI', 'body' => 'Cafe AI is a cafe in Kemang serving single-origin Indonesian coffee, a homestyle kitchen and bread baked every morning. We are open every day from 08:00 to 22:00.'],
                    'ja' => ['title' => 'Cafe AIについて', 'body' => 'Cafe AIはクマンにあるカフェで、インドネシア産シングルオリジンのコーヒー、家庭的なキッチン、毎朝焼くパンをご用意しています。毎日8:00〜22:00に営業しています。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Kebijakan reservasi meja', 'tags' => ['reservasi', 'reservation', 'batal', 'cancel'], 'image_url' => null,
                'body' => 'Meja ditahan selama 15 menit dari awal sesi. Pembatalan gratis sampai 2 jam sebelum sesi dimulai. Setiap sesi berlangsung 2 jam; bila kafe tidak penuh, Anda boleh tinggal lebih lama. Biaya reservasi hanya berlaku untuk Ruang Meeting Privat dan dihitung sebagai bagian dari minimum belanja.',
                'translations' => [
                    'en' => ['title' => 'Table reservation policy', 'body' => 'Tables are held for 15 minutes from the start of the slot. Cancellation is free up to 2 hours before the slot begins. Each slot is 2 hours; if the cafe is not full you are welcome to stay longer. The reservation fee applies to the Private Meeting Room only and counts towards its minimum spend.'],
                    'ja' => ['title' => 'テーブル予約について', 'body' => 'お席は枠の開始から15分間お取り置きします。枠の開始2時間前までは無料でキャンセルできます。1枠は2時間ですが、混雑していなければ延長も可能です。予約料はプライベートルームのみで、最低ご利用金額に充当されます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Alergi dan kebutuhan diet', 'tags' => ['alergi', 'allergy', 'allergen', 'vegan', 'halal', 'diet'], 'image_url' => null,
                'body' => 'Semua menu kami halal. Dapur kami menangani susu, gluten, telur, kacang, kedelai, dan makanan laut, sehingga kami tidak dapat menjamin bebas silang kontaminasi. Informasi alergen di menu hanya panduan; mohon beri tahu barista bila Anda memiliki alergi serius.',
                'translations' => [
                    'en' => ['title' => 'Allergies and dietary needs', 'body' => 'Everything on our menu is halal. Our kitchen handles milk, gluten, egg, nuts, soy and seafood, so we cannot guarantee freedom from cross-contact. Allergen details on the menu are a guide only; please tell your barista about any serious allergy.'],
                    'ja' => ['title' => 'アレルギーと食事制限', 'body' => 'メニューはすべてハラールです。厨房では乳、小麦、卵、ナッツ、大豆、魚介を扱っているため、コンタミネーションを完全に防ぐことはできません。メニューのアレルゲン情報は目安です。重いアレルギーがある場合は必ずスタッフにお伝えください。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Metode pembayaran', 'tags' => ['bayar', 'payment', 'qris', 'kartu', 'cash'], 'image_url' => null,
                'body' => 'Kami menerima tunai, QRIS, kartu debit, dan kartu kredit Visa serta Mastercard. Reservasi lewat situs ini tidak memerlukan pembayaran di muka.',
                'translations' => [
                    'en' => ['title' => 'Payment methods', 'body' => 'We accept cash, QRIS, debit cards, and Visa and Mastercard credit cards. Reserving through this site does not require payment upfront.'],
                    'ja' => ['title' => 'お支払い方法', 'body' => '現金、QRIS、デビットカード、VisaとMastercardのクレジットカードをご利用いただけます。このサイトからのご予約に前払いは不要です。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Wi-Fi gratis dan colokan listrik', 'tags' => ['wifi', 'internet', 'colokan', 'power', 'outlet'], 'image_url' => 'images/scenes/seating.svg',
                'body' => 'Wi-Fi cepat gratis untuk semua tamu; kata sandinya ada di struk atau tanyakan kepada barista. Colokan tersedia di Indoor Lounge, Bar Counter, dan Ruang Meeting Privat.',
                'translations' => [
                    'en' => ['title' => 'Free Wi-Fi and power outlets', 'body' => 'Fast Wi-Fi is free for all guests; the password is on your receipt or just ask a barista. Power outlets are available in the Indoor Lounge, the Bar Counter and the Private Meeting Room.'],
                    'ja' => ['title' => '無料Wi-Fiと電源', 'body' => '高速Wi-Fiはすべてのお客様が無料でご利用いただけます。パスワードはレシートに記載、またはスタッフにお尋ねください。電源はインドアラウンジ、カウンター席、プライベートルームにあります。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Ramah untuk bekerja', 'tags' => ['kerja', 'work', 'laptop', 'coworking', 'meeting'], 'image_url' => 'images/scenes/counter.svg',
                'body' => 'Laptop diperbolehkan setiap hari. Pada akhir pekan pukul 17.00 sampai 21.00, meja di Indoor Lounge diprioritaskan untuk tamu yang makan dan minum.',
                'translations' => [
                    'en' => ['title' => 'Work-friendly', 'body' => 'Laptops are welcome every day. On weekends from 17:00 to 21:00, Indoor Lounge tables are kept for guests who are dining.'],
                    'ja' => ['title' => '作業にもおすすめ', 'body' => 'ノートパソコンは毎日ご利用いただけます。週末の17:00〜21:00は、インドアラウンジのテーブルをお食事のお客様優先とさせていただきます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Musik akustik live', 'tags' => ['musik', 'music', 'live', 'akustik', 'acoustic'], 'image_url' => 'images/scenes/terrace.svg',
                'body' => 'Penampilan musik akustik live setiap Jumat dan Sabtu pukul 19.30 sampai 21.30 di Garden Terrace. Tidak ada biaya tambahan.',
                'translations' => [
                    'en' => ['title' => 'Live acoustic music', 'body' => 'Live acoustic music every Friday and Saturday from 19:30 to 21:30 on the Garden Terrace. There is no cover charge.'],
                    'ja' => ['title' => 'ライブアコースティック', 'body' => '毎週金曜と土曜の19:30〜21:30、ガーデンテラスでアコースティックライブを開催します。入場料はかかりません。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Teras ramah hewan peliharaan', 'tags' => ['hewan', 'pet', 'anjing', 'dog', 'kucing', 'cat'], 'image_url' => 'images/seating/garden-terrace.svg',
                'body' => 'Anjing dan kucing yang jinak boleh duduk bersama Anda di Garden Terrace dengan tali atau di dalam tas. Kami menyediakan mangkuk air.',
                'translations' => [
                    'en' => ['title' => 'Pet-friendly terrace', 'body' => 'Well-behaved dogs and cats are welcome on the Garden Terrace, on a leash or in a carrier. We provide water bowls.'],
                    'ja' => ['title' => 'ペット同伴OKのテラス', 'body' => 'おとなしいワンちゃん・猫ちゃんは、リードまたはキャリーに入れてガーデンテラスにご同伴いただけます。お水もご用意しています。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_EVENTS, 'title' => 'Acara privat dan katering', 'tags' => ['acara', 'event', 'private', 'catering', 'arisan', 'ulang tahun', 'birthday'], 'image_url' => 'images/seating/private-room.svg',
                'body' => 'Ruang Meeting Privat dapat dipakai untuk 6 sampai 12 orang. Kami juga menerima pesanan katering dan kue ulang tahun minimal 2 hari sebelumnya. Untuk acara khusus, hubungi tim kami agar kami siapkan penawarannya.',
                'translations' => [
                    'en' => ['title' => 'Private events and catering', 'body' => 'The Private Meeting Room fits 6 to 12 people. We also take catering and birthday cake orders at least 2 days ahead. For special events, contact our team and we will prepare a quote.'],
                    'ja' => ['title' => '貸切イベントとケータリング', 'body' => 'プライベートルームは6〜12名様でご利用いただけます。ケータリングやバースデーケーキは2日前までにご注文ください。特別なイベントはスタッフにご相談いただければお見積りいたします。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_EVENTS, 'title' => 'Workshop kopi dan cupping', 'tags' => ['workshop', 'cupping', 'kopi', 'coffee', 'kelas'], 'image_url' => 'images/scenes/backbar.svg',
                'body' => 'Setiap Minggu pagi pukul 10.00 kami mengadakan sesi cupping dan workshop seduh manual untuk maksimal 8 peserta. Pendaftaran lewat tim kami.',
                'translations' => [
                    'en' => ['title' => 'Coffee workshops and cupping', 'body' => 'Every Sunday at 10:00 we hold a cupping session and manual brewing workshop for up to 8 people. Sign up through our team.'],
                    'ja' => ['title' => 'コーヒーワークショップとカッピング', 'body' => '毎週日曜の10:00に、最大8名様でのカッピングとハンドドリップのワークショップを開催します。お申し込みはスタッフまで。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_LOCATION, 'title' => 'Lokasi dan parkir', 'tags' => ['lokasi', 'location', 'parkir', 'parking', 'alamat', 'address', 'motor', 'mobil'], 'image_url' => 'images/scenes/window.svg',
                'body' => 'Kami berada di Jl. Kemang Raya No. 21, Jakarta Selatan, sekitar 5 menit berjalan kaki dari halte Kemang. Parkir mobil terbatas untuk sekitar 10 mobil; parkir motor tersedia di depan kafe.',
                'translations' => [
                    'en' => ['title' => 'Location and parking', 'body' => 'We are at Jl. Kemang Raya No. 21, South Jakarta, about a 5-minute walk from the Kemang bus stop. Car parking is limited to around 10 cars; motorbike parking is in front of the cafe.'],
                    'ja' => ['title' => 'アクセスと駐車場', 'body' => '所在地は南ジャカルタのJl. Kemang Raya No. 21で、クマンのバス停から徒歩約5分です。駐車場は車約10台分に限られ、バイクは店の前に停められます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_LOCATION, 'title' => 'Pesan antar dan bawa pulang', 'tags' => ['delivery', 'antar', 'takeaway', 'bawa pulang', 'gofood', 'grabfood'], 'image_url' => null,
                'body' => 'Semua menu bisa dibawa pulang. Pesan antar tersedia lewat GoFood dan GrabFood dengan jam yang sama dengan jam buka kafe.',
                'translations' => [
                    'en' => ['title' => 'Delivery and takeaway', 'body' => 'Everything on the menu is available to take away. Delivery is available through GoFood and GrabFood during the cafe\'s opening hours.'],
                    'ja' => ['title' => 'デリバリーとテイクアウト', 'body' => 'すべてのメニューをテイクアウトできます。デリバリーはGoFoodとGrabFoodで、営業時間内にご利用いただけます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apakah boleh datang tanpa reservasi?', 'tags' => ['walk-in', 'tanpa reservasi', 'langsung datang'], 'image_url' => null,
                'body' => 'Boleh. Sebagian besar meja kami diperuntukkan bagi tamu yang datang langsung. Reservasi disarankan untuk akhir pekan, rombongan di atas 4 orang, dan Ruang Meeting Privat.',
                'translations' => [
                    'en' => ['title' => 'Do you take walk-ins?', 'body' => 'Yes. Most of our tables are kept for walk-in guests. Reservations are recommended for weekends, groups of more than 4, and the Private Meeting Room.'],
                    'ja' => ['title' => '予約なしでも入れますか？', 'body' => 'はい。ほとんどのお席は予約なしのお客様用です。週末、5名様以上、プライベートルームはご予約をおすすめします。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FAQ, 'title' => 'Bolehkah membawa kue dari luar?', 'tags' => ['kue', 'cake', 'bawa', 'outside food', 'corkage'], 'image_url' => null,
                'body' => 'Kue ulang tahun dari luar boleh dibawa dengan biaya potong Rp 25.000 per kue. Makanan dan minuman lain dari luar tidak diperkenankan.',
                'translations' => [
                    'en' => ['title' => 'Can I bring a cake from outside?', 'body' => 'Outside birthday cakes are welcome for a cake-cutting fee of Rp 25,000 per cake. Other outside food and drinks are not allowed.'],
                    'ja' => ['title' => 'ケーキの持ち込みはできますか？', 'body' => 'バースデーケーキの持ち込みは、1台につきカット代Rp 25,000で承ります。その他の食べ物・飲み物の持ち込みはご遠慮ください。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apakah ada musala?', 'tags' => ['musala', 'mushola', 'ibadah', 'prayer', 'sholat'], 'image_url' => null,
                'body' => 'Ada musala kecil di sebelah toilet, lengkap dengan perlengkapan salat. Terbuka selama jam buka kafe.',
                'translations' => [
                    'en' => ['title' => 'Is there a prayer room?', 'body' => 'There is a small prayer room next to the restrooms, with prayer mats and garments. It is open during the cafe\'s opening hours.'],
                    'ja' => ['title' => '礼拝室はありますか？', 'body' => 'トイレの隣に小さな礼拝室があり、礼拝用の敷物と衣類をご用意しています。営業時間中はいつでもご利用いただけます。'],
                ],
            ],
        ];
    }
}
