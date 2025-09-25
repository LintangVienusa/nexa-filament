<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaryComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::connection('mysql_employees')->table('SalaryComponents')->insert([
            [
                'component_name' => 'Basic Salary',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'component_name' => 'Overtime',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'component_name' => 'tunjangan',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'component_name' => 'No Attendance',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'component_name' => 'PPh 21',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'component_name' => 'BPJS Kesehatan',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            

            [
                'component_name' => 'BPJS Kesehatan',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'component_name' => 'BPJS Ketenagakerjaan',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'component_name' => 'BPJS Ketenagakerjaan',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
