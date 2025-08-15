<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create admin user manually instead of using factory
        User::create([
            'name' => 'Admin',
            'email' => 'admin@smard.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }
}
