<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MappingRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
                    [
                        'province_name' => 'JAWA TENGAH',
                        'province_code' => 'JATENG',
                        'regency_name' => 'BREBES',
                        'regency_code' => 'BRB',
                        'station_name' => 'BUMIAYU',
                        'station_code' => 'BMA',
                        'village_name' => 'DUKUH TURI',
                        'village_code' => 'DKT',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'province_name' => 'JAWA TENGAH',
                        'province_code' => 'JATENG',
                        'regency_name' => 'BREBES',
                        'regency_code' => 'BRB',
                        'station_name' => 'BUMIAYU',
                        'station_code' => 'BMA',
                        'village_name' => 'BUMIAYU',
                        'village_code' => 'BMA',
                        'created_at' => now(),
                        'updated_at' => now(),
                        
                    ],
            ];

            foreach ($data as $entry) {
                 DB::connection('mysql_inventory')->table('MappingRegion')->insert($entry);
            }
        
    }
}
