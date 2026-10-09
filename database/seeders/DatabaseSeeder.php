<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            $this->call([
                UserSeeder::class,
                CafeSeeder::class,
                CafeUserSeeder::class,
                MenuItemSeeder::class,
                SeatingAreaSeeder::class,
                SeatingImageSeeder::class,
                TableInventorySeeder::class,
                CafeKnowledgeItemSeeder::class,
            ]);
        }
    }
}
