<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::where('email', config('admin.email'))->first();

        if (! $admin) {
            User::factory()->create([
                'name' => config('admin.name'),
                'email' => config('admin.email'),
                'email_verified_at' => now(),
                'password' => bcrypt(config('admin.password')),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

    }
}
