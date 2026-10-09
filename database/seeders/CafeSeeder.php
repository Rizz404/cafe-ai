<?php

namespace Database\Seeders;

use App\Models\Cafe;
use Illuminate\Database\Seeder;

class CafeSeeder extends Seeder
{
    /**
     * The demo cafe's name, used by the other seeders to look it up.
     */
    public const NAME = 'Cafe AI';

    public function run(): void
    {
        Cafe::firstOrCreate(
            ['name' => self::NAME],
            [
                'slug' => Cafe::generateUniqueSlug(self::NAME),
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
    }
}
