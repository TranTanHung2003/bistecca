<?php

namespace Database\Seeders;

use App\Models\User;
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
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => 'fo',
            ],
        );

        User::updateOrCreate(
            ['email' => 'fo@noh.com'],
            [
                'name' => 'FO',
                'password' => bcrypt('NOH.dng@23'),
                'role' => 'fo',
            ],
        );

        User::updateOrCreate(
            ['email' => 'fb@noh.com'],
            [
                'name' => 'FB',
                'password' => bcrypt('NOH.dng@26'),
                'role' => 'fb',
            ],
        );
    }
}
