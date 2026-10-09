<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * The demo cafe owner's email, used by the other seeders to look them up.
     */
    public const OWNER_EMAIL = 'owner@cafeai.test';

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('Demo login: '.self::OWNER_EMAIL.' — password: password');
    }
}
