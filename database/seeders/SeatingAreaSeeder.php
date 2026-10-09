<?php

namespace Database\Seeders;

use App\Models\Cafe;
use Illuminate\Database\Seeder;

class SeatingAreaSeeder extends Seeder
{
    public function run(): void
    {
        $cafe = Cafe::where('name', CafeSeeder::NAME)->firstOrFail();
        $sort = 0;

        foreach ($this->seatingDefinitions() as $definition) {
            $cafe->seatingAreas()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seatingDefinitions(): array
    {
        return [
            [
                'slug' => 'indoor-lounge', 'name' => 'Indoor Lounge', 'area_type' => 'indoor', 'min_guests' => 1, 'max_guests' => 4,
                'features' => ['wifi', 'power_outlet', 'air_conditioning', 'sofa'], 'reservation_fee' => 0, 'minimum_spend' => null,
                'description' => 'Ruang ber-AC dengan sofa empuk dan colokan di setiap meja, nyaman untuk ngobrol atau bekerja.',
                'translations' => [
                    'id' => ['name' => 'Lounge Dalam', 'description' => 'Ruang ber-AC dengan sofa empuk dan colokan di setiap meja, nyaman untuk ngobrol atau bekerja.'],
                    'en' => ['name' => 'Indoor Lounge', 'description' => 'An air-conditioned room with soft sofas and a power outlet at every table, good for catching up or working.'],
                    'ja' => ['name' => 'インドアラウンジ', 'description' => '空調の効いた室内に、ふかふかのソファと各テーブルの電源。おしゃべりにも作業にも。'],
                ],
            ],
            [
                'slug' => 'garden-terrace', 'name' => 'Garden Terrace', 'area_type' => 'outdoor', 'min_guests' => 1, 'max_guests' => 6,
                'features' => ['garden_view', 'pet_friendly', 'smoking_area', 'wifi'], 'reservation_fee' => 0, 'minimum_spend' => null,
                'description' => 'Teras taman teduh dengan lampu gantung hangat di malam hari. Boleh membawa hewan peliharaan.',
                'translations' => [
                    'id' => ['name' => 'Teras Taman', 'description' => 'Teras taman teduh dengan lampu gantung hangat di malam hari. Boleh membawa hewan peliharaan.'],
                    'en' => ['name' => 'Garden Terrace', 'description' => 'A shady garden terrace with warm string lights in the evening. Pets are welcome.'],
                    'ja' => ['name' => 'ガーデンテラス', 'description' => '木陰のガーデンテラス。夜は温かな電球が灯ります。ペット同伴もOKです。'],
                ],
            ],
            [
                'slug' => 'bar-counter', 'name' => 'Bar Counter', 'area_type' => 'bar', 'min_guests' => 1, 'max_guests' => 2, 'features' => ['power_outlet', 'wifi', 'quiet'],
                'reservation_fee' => 0, 'minimum_spend' => null,
                'description' => 'Kursi tinggi menghadap mesin espresso, pas untuk melihat barista bekerja atau menikmati kopi sendirian.',
                'translations' => [
                    'id' => ['name' => 'Meja Bar', 'description' => 'Kursi tinggi menghadap mesin espresso, pas untuk melihat barista bekerja atau menikmati kopi sendirian.'],
                    'en' => ['name' => 'Bar Counter', 'description' => 'High stools facing the espresso machine, ideal for watching the baristas or enjoying a coffee on your own.'],
                    'ja' => ['name' => 'カウンター席', 'description' => 'エスプレッソマシンを望むハイスツール。バリスタの手元を眺めながら、ひとりの時間にぴったりです。'],
                ],
            ],
            [
                'slug' => 'private-room', 'name' => 'Private Meeting Room', 'area_type' => 'private', 'min_guests' => 6, 'max_guests' => 12,
                'features' => ['air_conditioning', 'projector', 'wifi', 'quiet'], 'reservation_fee' => 150000, 'minimum_spend' => 600000,
                'description' => 'Ruang privat untuk rapat kecil, arisan, atau ulang tahun, lengkap dengan proyektor dan layanan antar ke meja.',
                'translations' => [
                    'id' => ['name' => 'Ruang Meeting Privat', 'description' => 'Ruang privat untuk rapat kecil, arisan, atau ulang tahun, lengkap dengan proyektor dan layanan antar ke meja.'],
                    'en' => ['name' => 'Private Meeting Room', 'description' => 'A private room for small meetings, gatherings or birthdays, with a projector and table service.'],
                    'ja' => ['name' => 'プライベートルーム', 'description' => '小規模なミーティングや集まり、誕生日にご利用いただける個室。プロジェクターとテーブルサービス付きです。'],
                ],
            ],
        ];
    }
}
