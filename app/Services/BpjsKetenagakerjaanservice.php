<?php

namespace App\Services;

class BpjsKetenagakerjaanService
{
    public function hitungkk(int $gaji, float $jkkRate = 0.0024): array
    {
        // Batas gaji untuk JP (2025 = 9.559.600)
        $batasJP = 9559600;
        $gajiJP = min($gaji, $batasJP);

        $hasil = [
            'dasar_perhitungan' => $gaji,
            'jkk' => $gaji * $jkkRate,         // Ditanggung perusahaan
            'jkm' => $gaji * 0.003,           // Ditanggung perusahaan
            'jht_perusahaan' => $gaji * 0.037,
            'jht_karyawan' => $gaji * 0.02,
            'jp_perusahaan' => $gajiJP * 0.02,
            'jp_karyawan' => $gajiJP * 0.01,
        ];

        $hasil['total_perusahaan'] = $hasil['jkk'] + $hasil['jkm'] + $hasil['jht_perusahaan'] + $hasil['jp_perusahaan'];
        $hasil['total_karyawan']   = $hasil['jht_karyawan'] + $hasil['jp_karyawan'];
        $hasil['total_iuran']      = $hasil['total_perusahaan'] + $hasil['total_karyawan'];

        return $hasil;
    }
}