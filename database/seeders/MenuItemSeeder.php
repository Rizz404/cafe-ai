<?php

namespace Database\Seeders;

use App\Models\Cafe;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $cafe = Cafe::where('name', CafeSeeder::NAME)->firstOrFail();
        $sort = 0;

        foreach ($this->menuDefinitions() as $definition) {
            $cafe->menuItems()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'image_url' => 'images/menu/'.$definition['slug'].'.svg', 'sort_order' => $sort++, 'is_active' => true]
            );
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
}
