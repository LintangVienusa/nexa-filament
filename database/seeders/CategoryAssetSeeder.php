<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CategoryAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::connection('mysql_inventory')->table('CategoryAsset')->insert([
            [
                'category_code' => 'NETWK',
                'category_name' => 'Perangkat Jaringan',
                'description' => 'Switch, Router, Access Point, OLT, ONU, dan perangkat jaringan lainnya',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_code' => 'CABLE',
                'category_name' => 'Kabel & Aksesoris',
                'description' => 'Kabel FO, UTP, konektor, splitter, patchcord, ducting, dan perlengkapan instalasi jaringan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_code' => 'TOOL',
                'category_name' => 'Peralatan Teknik',
                'description' => 'Tang crimping, fusion splicer, OTDR, power meter, dan peralatan kerja lapangan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_code' => 'ELEC',
                'category_name' => 'Elektronik & Komputer',
                'description' => 'Laptop, PC, UPS, printer, monitor, dan perangkat elektronik kantor lainnya',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_code' => 'VEHCL',
                'category_name' => 'Kendaraan Operasional',
                'description' => 'Mobil operasional, motor teknisi, dan kendaraan pengangkut material',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_code' => 'FURN',
                'category_name' => 'Furnitur Kantor',
                'description' => 'Meja, kursi, lemari, rak, dan perlengkapan interior kantor lainnya',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_code' => 'SAFET',
                'category_name' => 'Perlengkapan Keselamatan',
                'description' => 'APD, rompi, helm, safety shoes, harness, dan peralatan keamanan kerja',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_code' => 'OTHRS',
                'category_name' => 'Lain-lain',
                'description' => 'Kategori untuk aset lain yang tidak masuk kategori utama',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
