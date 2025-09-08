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

        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@nexa-erp.localhost',
            'email_verified_at' => now(),
            'password' => bcrypt('admin'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
