<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\User;
use Illuminate\Database\Seeder;

class CafeUserSeeder extends Seeder
{
    public function run(): void
    {
        $cafe = Cafe::where('name', CafeSeeder::NAME)->firstOrFail();
        $owner = User::where('email', UserSeeder::OWNER_EMAIL)->firstOrFail();

        $cafe->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner', 'status' => 'active'],
        ]);
    }
}
