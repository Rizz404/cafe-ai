<?php

namespace Database\Seeders;

use App\Models\Cafe;
use Illuminate\Database\Seeder;

class SeatingImageSeeder extends Seeder
{
    public function run(): void
    {
        $cafe = Cafe::where('name', CafeSeeder::NAME)->firstOrFail();

        foreach ($this->imageDefinitions() as $areaSlug => $images) {
            $area = $cafe->seatingAreas()->where('slug', $areaSlug)->firstOrFail();
            $sort = 0;

            foreach ($images as $image) {
                $area->images()->updateOrCreate(
                    ['image_url' => $image['url']],
                    ['tags' => $image['tags'], 'alt_text' => $image['alt'], 'sort_order' => $sort++]
                );
            }
        }
    }

    /**
     * Images per seating area, keyed by the area's slug.
     *
     * @return array<string, list<array{url: string, tags: list<string>, alt: string}>>
     */
    private function imageDefinitions(): array
    {
        return [
            'indoor-lounge' => [
                ['url' => 'images/seating/indoor-lounge.svg', 'tags' => ['sofa'], 'alt' => 'Sofa dan meja di Indoor Lounge'],
                ['url' => 'images/scenes/seating.svg', 'tags' => ['room'], 'alt' => 'Suasana Indoor Lounge'],
            ],
            'garden-terrace' => [
                ['url' => 'images/seating/garden-terrace.svg', 'tags' => ['garden'], 'alt' => 'Meja di Garden Terrace'],
                ['url' => 'images/scenes/terrace.svg', 'tags' => ['terrace'], 'alt' => 'Suasana Garden Terrace di sore hari'],
            ],
            'bar-counter' => [
                ['url' => 'images/seating/bar-counter.svg', 'tags' => ['bar'], 'alt' => 'Kursi tinggi di Bar Counter'],
                ['url' => 'images/scenes/counter.svg', 'tags' => ['counter'], 'alt' => 'Bar Counter dengan mesin espresso'],
            ],
            'private-room' => [
                ['url' => 'images/seating/private-room.svg', 'tags' => ['meeting'], 'alt' => 'Meja panjang di Private Meeting Room'],
                ['url' => 'images/scenes/reservation.svg', 'tags' => ['table'], 'alt' => 'Meja yang sudah direservasi'],
            ],
        ];
    }
}
