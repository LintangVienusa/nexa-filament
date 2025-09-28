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
                'component_name' => 'Positional Allowance',
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
                'component_name' => 'Marriage Allowance',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            

            [
                'component_name' => 'Child Allowance',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'component_name' => 'Allowance',
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
                'component_name' => 'JHT BPJS TK',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            
            [
                'component_name' => 'JKK BPJS TK',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'component_name' => 'JKM BPJS TK',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'component_name' => 'JP BPJS TK',
                'component_type' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            

            [
                'component_name' => 'JHT BPJS TK',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            
            [
                'component_name' => 'JKK BPJS TK',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'component_name' => 'JKM BPJS TK',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'component_name' => 'JP BPJS TK',
                'component_type' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
