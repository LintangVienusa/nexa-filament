<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('AppVersion')->insert([
            'platform' => 'android',
            'min_build' => 5,
            'latest_build' => 5,
            'version' => '2.0.0',
            'store_url' => 'https://play.google.com/apps/test/com.dapoerpoesatnoesantara/5',
            'update_message' => 'Silakan update aplikasi ke versi terbaru untuk melanjutkan.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
