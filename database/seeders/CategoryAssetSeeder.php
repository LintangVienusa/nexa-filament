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
        $categories = [
                        [
                            'category_id' => 'CA0001',
                            'category_code' => 'OLT',
                            'category_name' => 'Optical Line Terminal',
                            'description' => 'Perangkat OLT (pusat jaringan FO) yang digunakan untuk distribusi koneksi ke pelanggan',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0002',
                            'category_code' => 'ONU',
                            'category_name' => 'Optical Network Unit',
                            'description' => 'Perangkat ONU/ONT yang dipasang di sisi pelanggan',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0003',
                            'category_code' => 'RTR',
                            'category_name' => 'Router',
                            'description' => 'Perangkat router jaringan untuk routing dan manajemen trafik',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0004',
                            'category_code' => 'SWT',
                            'category_name' => 'Switch',
                            'description' => 'Perangkat switch jaringan (access, distribution, core)',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0005',
                            'category_code' => 'AP',
                            'category_name' => 'Access Point',
                            'description' => 'Perangkat wireless untuk distribusi sinyal Wi-Fi',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            
                            'category_id' => 'CA0006',
                            'category_code' => 'CBFO',
                            'category_name' => 'Kabel Fiber Optik',
                            'description' => 'Kabel FO udara atau ducting untuk distribusi jaringan',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            
                            'category_id' => 'CA0007',
                            'category_code' => 'CBUTP',
                            'category_name' => 'Kabel UTP',
                            'description' => 'Kabel UTP indoor/outdoor untuk koneksi LAN',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0008',
                            'category_code' => 'CONN',
                            'category_name' => 'Konektor & Aksesoris',
                            'description' => 'Konektor SC/LC, splitter, patchcord, ducting, dll',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            
                            'category_id' => 'CA0009',
                            'category_code' => 'TJ',
                            'category_name' => 'Tiang Jaringan',
                            'description' => 'Tiang penyangga kabel FO atau perangkat outdoor',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0010',
                            'category_code' => 'TOOL',
                            'category_name' => 'Peralatan Teknik',
                            'description' => 'Tang crimping, fusion splicer, OTDR, power meter, ladder, dll',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0011',
                            'category_code' => 'ELEC',
                            'category_name' => 'Perangkat Elektronik & Komputer',
                            'description' => 'Laptop, PC, UPS, printer, dan perangkat elektronik lainnya',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            
                            'category_id' => 'CA0012',
                            'category_code' => 'VEH',
                            'category_name' => 'Kendaraan Operasional',
                            'description' => 'Mobil operasional, motor teknisi, kendaraan pengangkut material',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0013',
                            'category_code' => 'SAFE',
                            'category_name' => 'Perlengkapan Keselamatan',
                            'description' => 'APD, rompi, helm, safety shoes, harness, dan perlengkapan keamanan kerja',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        [
                            'category_id' => 'CA0014',
                            'category_code' => 'OTH',
                            'category_name' => 'Lain-lain',
                            'description' => 'Kategori untuk aset lain yang tidak termasuk kategori utama',
                            'created_by' => 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    ];

        DB::connection('mysql_inventory')->table('CategoryAsset')->insert($categories);
    }
}
